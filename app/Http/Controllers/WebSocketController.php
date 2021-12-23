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
                'profile_picture' => '',
                'username' => '',
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
            $event = "";
            $data = json_decode($msg);
            if (is_object($data) && property_exists($data, 'event')) {
                $event = $data->event;
            }
            switch ($event) {
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
            $conn->send(json_encode([
                'event' => 'event-error',
                'event-name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
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
                'username' => ['required', 'string',],
                'profile_picture' => ['required', 'string',],
            ]);

            if ($validator->fails()) {
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event-name' => $data->event,
                    'message' => 'Invalid or missing input fields',
                    'errors' => $validator->errors()->toArray(),
                ]));
                return;
            }

            $otp = Otp::where('code', $data->code)->where('user_id', $data->user_id)->where('purpose', 'authentication')->first();

            if (is_null($otp)) {
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event-name' => $data->event,
                    'message' => 'Invalid OTP provided',
                    'errors' => [],
                ]));
                return;
            }

            if ($otp->expires_at->lt(now())) {
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event-name' => $data->event,
                    'message' => 'Access code has expired',
                    'errors' => [],
                ]));
                return;
            }

            $otp->expires_at = now();//expire the token since it has been used
            $otp->save();

            $this->connections[$connection->resourceId] = [
                'socket_connection' => $connection,
                'user_id' => $data->user_id,
                'profile_picture' => $data->profile_picture,
                'username' => $data->username,
                'is_authenticated' => true,
            ];
            $user_connections = [];
            if (array_key_exists($data->user_id, $this->map_user_id_to_connections)) {
                $user_connections = $this->map_user_id_to_connections[$data->user_id];
            }
            $this->map_user_id_to_connections[$data->user_id] = array_unique(array_merge($user_connections, [$connection->resourceId]));
            
            $connection->send(json_encode([
                'event' => 'event-success',
                'event-name' => $data->event,
                'message' => 'User authenticated successfully',
                'data' => [],
            ]));
        } catch (\Exception $exception) {
            $connection->send(json_encode([
                'event' => 'event-error',
                'event-name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
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
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event-name' => $data->event,
                    'message' => 'Invalid or missing input fields',
                    'errors' => $validator->errors()->toArray(),
                ]));
                return;
            }

            //check if connection has been authenticated
            $connection_auth_data = $this->connections[$connection->resourceId];
            if (is_null($connection_auth_data) || ! is_array($connection_auth_data) || $connection_auth_data['is_authenticated'] !== true) {
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event-name' => $data->event,
                    'message' => 'This connection is not authenticated',
                    'errors' => [],
                ]));
                return;
            }

            // add connection to channel subscribers
            $channel_name = $data->channel_name;
            $channel_subscribers = [];
            if (array_key_exists($channel_name, $this->rtm_channel_subscribers)) {
                $channel_subscribers = $this->rtm_channel_subscribers[$channel_name];
            }
 
            $this->rtm_channel_subscribers[$channel_name] = array_unique(array_merge($channel_subscribers, [$connection->resourceId]));

            $connection->send(json_encode([
                'event' => 'event-success',
                'event-name' => $data->event,
                'message' => 'Channel joined successfully',
                'data' => [],
            ]));
        } catch (\Exception $exception) {
            $connection->send(json_encode([
                'event' => 'event-error',
                'event-name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
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
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event-name' => $data->event,
                    'message' => 'Invalid or missing input fields',
                    'errors' => $validator->errors()->toArray(),
                ]));
                return;
            }

            //check if connection has been authenticated
            $sender_auth_data = $this->connections[$connection->resourceId];
            if (is_null($sender_auth_data) || ! is_array($sender_auth_data) || $sender_auth_data['is_authenticated'] !== true) {
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event-name' => $data->event,
                    'message' => 'This connection is not authenticated',
                    'errors' => [],
                ]));
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
                    'event' => 'message-from-rtm-channel',
                    'channel_name' => $channel_name,
                    'message' => $data->message,
                    'user_id' => $sender_auth_data['user_id'],
                    'profile_picture' => $sender_auth_data['profile_picture'],
                    'username' => $sender_auth_data['username'],
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
            $connection->send(json_encode([
                'event' => 'event-error',
                'event-name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
            Log::error($exception);
            return;
        }
    }
}
