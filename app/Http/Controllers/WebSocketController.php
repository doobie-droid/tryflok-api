<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class WebSocketController extends Controller implements MessageComponentInterface
{
    private $connections = [];
    private $map_user_id_to_connections = [];
    private $rtm_channel_subscribers = [];

    /**
     * When a new connection is opened it will be passed to this method
     * @param  ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    public function onOpen(ConnectionInterface $conn){
        try {
            $this->connections[$conn->resourceId] = [
                'socket_connection' => $conn,
                'user_id' => null,
                'is_authenticated' => false,
            ];
        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
    
     /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    public function onClose(ConnectionInterface $conn){
        try {
            $user_id = null;
            if (array_key_exists('user_id', $this->connections[$conn->resourceId])) {
                $user_id = $this->connections[$conn->resourceId]['user_id'];
            }
            
            unset($this->connections[$conn->resourceId]);
            if (! is_null($user_id)) {
                $key = array_search($conn->resourceId,$this->map_user_id_to_connections[$user_id]);
                unset($this->map_user_id_to_connections[$user_id][$key]); 
            }
        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
    
     /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $conn
     * @param  \Exception $e
     * @throws \Exception
     */
    public function onError(ConnectionInterface $conn, \Exception $e){
        try {
            $user_id = null;
            if (array_key_exists('user_id', $this->connections[$conn->resourceId])) {
                $user_id = $this->connections[$conn->resourceId]['user_id'];
            }
            
            unset($this->connections[$conn->resourceId]);
            if (! is_null($user_id)) {
                $key = array_search($conn->resourceId,$this->map_user_id_to_connections[$user_id]);
                unset($this->map_user_id_to_connections[$user_id][$key]); 
            }
            $conn->close();
        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
    
     /**
     * Triggered when a client sends data through the socket
     * @param  \Ratchet\ConnectionInterface $conn The socket/connection that sent the message to your application
     * @param  string $msg The message received
     * @throws \Exception
     */
    public function onMessage(ConnectionInterface $conn, $msg){
        try {
            $action = "";
            $data = json_decode($msg);
            if (is_object($data) && property_exists($data, 'action')) {
                $action = $data->action;
            }
            switch ($action) {
                case 'authenticate':
                    $this->authenticateConnection($data, $conn);
                    break;
                case 'join-rtm-channel':
                    $this->joinRtmChannel($data, $conn);
                    break;
                case 'message-rtm-channel':
                    $this->messageRtmChannel($data, $conn);
                    break;
                case 'echo':
                    $echo = ['echo' => $data->message];
                    $conn->send(json_encode($echo));
                case 'notify-user':
                    break;
            }
        } catch (\Exception $exception) {
            $response = $this->respondBadRequest('Oops, an error occurred, please try again later')->getData();
            $conn->send(json_encode($response));
            Log::error($exception);
            return;
        }
    }

    private function authenticateConnection($data, $connection)
    {
        try {
            $validator = Validator::make((array) $data, [
                'code' => ['required', 'string',],
                'user_id' => ['required', 'string',],
            ]);

            if ($validator->fails()) {
                $response = $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray())->getData();
                $connection->send(json_encode($response));
                return;
            }

            $otp = Otp::where('code', $data->code)->where('user_id', $data->user_id)->where('purpose', 'authentication')->first();

            if (is_null($otp)) {
                $response = $this->respondBadRequest('Invalid OTP provided')->getData();
                $connection->send(json_encode($response));
                return;
            }

            if ($otp->expires_at->lt(now())) {
                $response = $this->respondBadRequest('Access code has expired')->getData();
                $connection->send(json_encode($response));
                return;
            }

            $otp->expires_at = now();//expire the token since it has been used
            $otp->save();

            $this->connections[$connection->resourceId] = [
                'socket_connection' => $connection,
                'user_id' => $data->user_id,
                'is_authenticated' => true,
            ];
            $user_connections = [];
            if (array_key_exists($data->user_id, $this->map_user_id_to_connections)) {
                $user_connections = $this->map_user_id_to_connections[$data->user_id];
            }
            $this->map_user_id_to_connections[$data->user_id] = array_unique(array_merge($user_connections, [$connection->resourceId]));
            
            $response = $this->respondWithSuccess('User authenticated successfully')->getData();
            $connection->send(json_encode($response));
        } catch (\Exception $exception) {
            $response = $this->respondBadRequest('Oops, an error occurred, please try again later')->getData();
            $connection->send(json_encode($response));
            Log::error($exception);
            return;
        }
    }

    private function joinRtmChannel($data, $connection)
    {
        try {
            $validator = Validator::make((array) $data, [
                'channel_name' => ['required', 'string',],
                'user_id' => ['required', 'string',],
            ]);

            if ($validator->fails()) {
                $response = $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray())->getData();
                $connection->send(json_encode($response));
                return;
            }

            //check if connection has been authenticated
            $connection_auth_data = $this->connections[$connection->resourceId];
            if (is_null($connection_auth_data) || ! is_array($connection_auth_data) || $connection_auth_data['is_authenticated'] !== true) {
                $response = $this->respondBadRequest('This connection is not authenticated')->getData();
                $connection->send(json_encode($response));
                return;
            }

            // add connection to channel subscribers
            $channel_name = $data->channel_name;
            $channel_subscribers = [];
            if (array_key_exists($channel_name, $this->rtm_channel_subscribers)) {
                $channel_subscribers = $this->rtm_channel_subscribers[$channel_name];
            }
 
            $this->rtm_channel_subscribers[$channel_name] = array_unique(array_merge($channel_subscribers, [$connection->resourceId]));

            $response = $this->respondWithSuccess('Channel joined successfully.')->getData();
            $connection->send(json_encode($response));
        } catch (\Exception $exception) {
            $response = $this->respondBadRequest('Oops, an error occurred, please try again later')->getData();
            $connection->send(json_encode($response));
            Log::error($exception);
            return;
        }
    }

    private function messageRtmChannel($data, $connection)
    {
        try {
            $validator = Validator::make((array) $data, [
                'channel_name' => ['required', 'string',],
                'user_id' => ['required', 'string',],
                'message' => ['required', 'string',],
            ]);

            if ($validator->fails()) {
                $response = $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray())->getData();
                $connection->send(json_encode($response));
                return;
            }

            //check if connection has been authenticated
            $connection_auth_data = $this->connections[$connection->resourceId];
            if (is_null($connection_auth_data) || ! is_array($connection_auth_data) || $connection_auth_data['is_authenticated'] !== true) {
                $response = $this->respondBadRequest('This connection is not authenticated')->getData();
                $connection->send(json_encode($response));
                return;
            }

            // send message to channel subscribers
            $channel_name = $data->channel_name;
            $channel_subscribers = [];
            if (array_key_exists($channel_name, $this->rtm_channel_subscribers)) {
                $channel_subscribers = $this->rtm_channel_subscribers[$channel_name];
            }

            foreach ($channel_subscribers as $key => $resourceId) {
                $message = [
                    'action' => 'message-from-rtm-channel',
                    'channel_name' => $channel_name,
                    'message' => $data->message,
                    'message_from' => $data->user_id,
                ];
                $connection_data = null;
                if (array_key_exists($resourceId, $this->connections)) {
                    $connection_data = $this->connections[$resourceId];
                } else {
                    unset($this->rtm_channel_subscribers[$channel_name][$key]);
                }

                if (! is_null($connection_data)) {
                    $connection_data['socket_connection']->send(json_encode($message));
                }
            }
        } catch (\Exception $exception) {
            $response = $this->respondBadRequest('Oops, an error occurred, please try again later')->getData();
            $connection->send(json_encode($response));
            Log::error($exception);
            return;
        }
    }
}
