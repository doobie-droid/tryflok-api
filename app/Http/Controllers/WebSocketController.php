<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class WebSocketController extends Controller implements MessageComponentInterface
{
    private $map_connections_to_user_id = [];
    private $map_user_id_to_resource_ids = [];

    /**
     * When a new connection is opened it will be passed to this method
     * @param  ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    public function onOpen(ConnectionInterface $conn){
        try {
            $this->map_connections_to_user_id[$conn->resourceId] = [
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
            $resource_id = $conn->resourceId;
            $user_id = $this->map_connections_to_user_id[$resource_id]['user_id'];
            unset($this->map_connections_to_user_id[$resource_id]);
            if (! is_null($user_id)) {
                $connection_ids = $this->map_user_id_to_resource_ids[$user_id];
                if (is_array($connection_ids)) {
                    $this->map_user_id_to_resource_ids[$user_id] = array_diff($connection_ids, [$resource_id]);
                }
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
            $resource_id = $conn->resourceId;
            $user_id = $this->map_connections_to_user_id[$resource_id]['user_id'];
            unset($this->map_connections_to_user_id[$resource_id]);
            if (! is_null($user_id)) {
                $connection_ids = $this->map_user_id_to_resource_ids[$user_id];
                if (is_array($connection_ids)) {
                    $this->map_user_id_to_resource_ids[$user_id] = array_diff($connection_ids, [$resource_id]);
                }
            }
            Log::error($e);
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
            $data = json_decode($msg);
            switch ($data->action) {
                case 'authenticate':
                    $this->authenticateConnection($data, $conn);
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
                'code' => ['required', 'string', 'exists:otps,code',],
                'user_id' => ['required', 'string', 'exists:users,id'],
            ]);

            if ($validator->fails()) {
                $response = $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray())->getData();
                $connection->send(json_encode($response));
                Log::error($exception);
                return;
            }

            $otp = Otp::where('code', $data->code)->where('user_id', $data->user_id)->where('purpose', 'authentication')->first();

            if (is_null($otp)) {
                $response = $this->respondBadRequest('Invalid OTP provided')->getData();
                $connection->send(json_encode($response));
                Log::error($exception);
                return;
            }

            if ($otp->expires_at->lt(now())) {
                $response = $this->respondBadRequest('Access code has expired')->getData();
                $connection->send(json_encode($response));
                Log::error($exception);
                return;
            }

            $otp->expires_at = now();//expire the token since it has been used
            $otp->save();

            $this->map_connections_to_user_id[$connection->resourceId] = [
                'socket_connection' => $connection,
                'user_id' => $data->user_id,
                'is_authenticated' => true,
            ];
            $this->map_user_id_to_resource_ids = array_unique(array_merge($this->map_user_id_to_resource_ids, [$connection->resourceId]));
            $response = $this->respondWithSuccess('User authenticated successfully')->getData();
            $connection->send(json_encode($response));
        } catch (\Exception $exception) {
            $response = $this->respondBadRequest('Oops, an error occurred, please try again later')->getData();
            $connection->send(json_encode($response));
            Log::error($exception);
            return;
        }
    }
}
