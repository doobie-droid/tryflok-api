<?php

namespace App\Http\Controllers;

use App\Constants\Constants;
use App\Jobs\Websocket\AuthenticateConnection;
use App\Jobs\Websocket\MuteRtmBroadcaster;
use App\Jobs\Websocket\UnmuteRtmBroadcaster;
use App\Jobs\Websocket\UpdateBroadcasterAgoraUid;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Tymon\JWTAuth\Facades\JWTAuth;

class WebSocketController extends Controller implements MessageComponentInterface
{
    private $ws_identity;
    private $connections = [];
    private $map_user_id_to_connections = [];
    private $rtm_channel_subscribers = [];

    public function __construct()
    {
        $this->ws_identity = Cache::store('redis_local')->rememberForever('ws-identity', function () {
            return Str::random(8) . date('YmdHis');
        });
    }
    /**
     * When a new connection is opened it will be passed to this method
     *
     * @param  ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    public function onOpen(ConnectionInterface $conn)
    {
        try {
            $this->connections[$conn->resourceId] = [
                'socket_connection' => $conn,
                'user_id' => null,
                'profile_picture' => '',
                'username' => '',
                'is_authenticated' => false,
            ];

            AuthenticateConnection::dispatch([
                'headers' => $conn->httpRequest->getHeaders(),
                'resource_id' => $conn->resourceId,
                'ws_identity' => $this->ws_identity,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
    
     /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
      *
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    public function onClose(ConnectionInterface $conn)
    {
        try {
            $user_id = null;
            if (array_key_exists('user_id', $this->connections[$conn->resourceId])) {
                $user_id = $this->connections[$conn->resourceId]['user_id'];
            }
            
            unset($this->connections[$conn->resourceId]);
            if (! is_null($user_id)) {
                $key = array_search($conn->resourceId, $this->map_user_id_to_connections[$user_id]);
                unset($this->map_user_id_to_connections[$user_id][$key]);
            }
        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
    
     /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
      *
     * @param  ConnectionInterface $conn
     * @param  \Exception $e
     * @throws \Exception
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        try {
            $user_id = null;
            if (array_key_exists('user_id', $this->connections[$conn->resourceId])) {
                $user_id = $this->connections[$conn->resourceId]['user_id'];
            }
            
            unset($this->connections[$conn->resourceId]);
            if (! is_null($user_id)) {
                $key = array_search($conn->resourceId, $this->map_user_id_to_connections[$user_id]);
                unset($this->map_user_id_to_connections[$user_id][$key]);
            }
            $conn->close();
        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
    
     /**
     * Triggered when a client sends data through the socket
      *
     * @param  \Ratchet\ConnectionInterface $conn The socket/connection that sent the message to your application
     * @param  string $msg The message received
     * @throws \Exception
     */
    public function onMessage(ConnectionInterface $conn, $msg)
    {
        try {
            $event = "";
            $data = json_decode($msg);
            if (is_object($data) && property_exists($data, 'event')) {
                $event = $data->event;
            }
            if (! $this->messageIsFromNodeOrApp($data)) {
                $user_is_authenticated = $this->checkConnectionIsAuthenticated($conn, $data);
                if (! $user_is_authenticated && $event !== 'join-rtm-channel') {
                    // only allow unauthenticated requests come from server node or api
                    // or requests that have to do with joining a channel
                    $conn->send(json_encode([
                        'event' => 'event-error',
                        'event_name' => $event,
                        'message' => 'Only authenticated users can perform this action',
                        'errors' => [],
                    ]));
                    return;
                }
            }
           
            switch ($event) {
                case 'join-rtm-channel':
                    $this->joinRtmChannel($data, $conn);
                    break;
                case 'message-rtm-channel':
                    $this->messageRtmChannel($data, $conn);
                    break;
                case 'add-rtm-channel-broadcaster':
                    $this->addRtmChannelBroadcaster($data, $conn);
                    break;
                case 'mute-rtm-channel-broadcaster':
                    $this->muteRtmChannelBroadcaster($data, $conn);
                    break;
                case 'unmute-rtm-channel-broadcaster':
                    $this->unmuteRtmChannelBroadcaster($data, $conn);
                    break;
                case 'request-broadcast-in-rtm-channel':
                    $this->requestBroadcastInRtmChannel($data, $conn);
                    break;
                case 'update-screen-share-status':
                    $this->updateScreenShareStatus($data, $conn);
                    break;
                case 'app-update-rtm-channel-subscribers-count':
                    $this->updateRtmChannelSubscribersCount($data, $conn);
                    break;
                case 'app-sign-out-other-devices':
                    $this->signOutOtherDevices($data, $conn);
                    break;
                case 'app-update-number-of-tips-for-content':
                    $this->updateNumberOfTipsForContent($data, $conn);
                    break;
                case 'app-set-connection-as-authenticated':
                    $this->setConnectionAsAuthenticated($data, $conn);
                    break;
                case 'echo':
                    $this->propagateToOtherNodes($data, $conn);
                    $echo = ['echo' => $data->message];
                    $conn->send(json_encode($echo));
                    break;
                case 'broadcast-test':
                    $this->propagateToOtherNodes($data, $conn);
                    foreach ($this->connections as $connection) {
                        $broadcast = ['broadcast' => $data->message];
                        $connection['socket_connection']->send(json_encode($broadcast));
                    }
                    break;
                case 'notify-user':
                    break;
            }
        } catch (\Exception $exception) {
            $conn->send(json_encode([
                'event' => 'event-error',
                'event_name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
            Log::error($exception);
            return;
        }
    }

    private function setConnectionAsAuthenticated($data, $connection)
    {
        try {
            $this->propagateToOtherNodes($data, $connection);
            if ($data->ws_identity !== $this->ws_identity) {
                return;
            }
            $resource_id = $data->resource_id;
            $resource_connection = $this->connections[$resource_id]['socket_connection'];
            $this->connections[$resource_id]['user_id'] = $data->user_id;
            $this->connections[$resource_id]['username'] = $data->username;
            $this->connections[$resource_id]['profile_picture'] = $data->profile_picture;
            $this->connections[$resource_id]['is_authenticated'] = true;

            $user_connections = [];
            if (array_key_exists($data->user_id, $this->map_user_id_to_connections)) {
                $user_connections = $this->map_user_id_to_connections[$data->user_id];
            }
            $this->map_user_id_to_connections[$data->user_id] = array_unique(array_merge($user_connections, [$resource_connection->resourceId]));
            
            $resource_connection->send(json_encode([
                'event' => 'event-success',
                'event_name' => $data->event,
                'message' => 'User authenticated successfully',
                'data' => [],
            ]));
        } catch (\Exception $exception) {
            $connection->send(json_encode([
                'event' => 'event-error',
                'event_name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
            Log::info(json_encode($data));
            Log::info($this->ws_identity);
            Log::error($exception);
            return;
        }
    }

    private function joinRtmChannel($data, $connection)
    {
        try {
            $validator = Validator::make((array) $data, [
                'channel_name' => ['required', 'string'],
                //'user_id' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event_name' => $data->event,
                    'message' => 'Invalid or missing input fields',
                    'errors' => $validator->errors()->toArray(),
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
                'event_name' => $data->event,
                'message' => 'Channel joined successfully',
                'data' => [],
            ]));
        } catch (\Exception $exception) {
            $connection->send(json_encode([
                'event' => 'event-error',
                'event_name' => $data->event,
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
                'channel_name' => ['required', 'string'],
                'user_id' => ['required', 'string'],
                'message' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event_name' => $data->event,
                    'message' => 'Invalid or missing input fields',
                    'errors' => $validator->errors()->toArray(),
                ]));
                return;
            }
            $this->propagateToOtherNodes($data, $connection);

            // send message to channel subscribers
            $channel_name = $data->channel_name;
            $channel_subscribers = [];
            if (array_key_exists($channel_name, $this->rtm_channel_subscribers)) {
                $channel_subscribers = $this->rtm_channel_subscribers[$channel_name];
            }

            $sender_auth_data = $this->connections[$connection->resourceId];

            $message = [
                'event' => 'message-from-rtm-channel',
                'channel_name' => $channel_name,
                'message' => $data->message,
                'user_id' => $sender_auth_data['user_id'],
                'profile_picture' => $sender_auth_data['profile_picture'],
                'username' => $sender_auth_data['username'],
            ];

            foreach ($channel_subscribers as $key => $resourceId) {
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
                'event_name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
            Log::error($exception);
            return;
        }
    }

    private function addRtmChannelBroadcaster($data, $connection)
    {
        try {
            $validator = Validator::make((array) $data, [
                'channel_name' => ['required', 'string'],
                'broadcaster_id' => ['required', 'string'],
                'agora_uid' => ['required', 'string'],
                'content_id' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event_name' => $data->event,
                    'message' => 'Invalid or missing input fields',
                    'errors' => $validator->errors()->toArray(),
                ]));
                return;
            }

            $this->propagateToOtherNodes($data, $connection);

            $content_id = $data->content_id;
            $broadcaster_id = $data->broadcaster_id;
            $agora_uid = $data->agora_uid;

            $channel_name = $data->channel_name;
            $channel_subscribers = [];
            if (array_key_exists($channel_name, $this->rtm_channel_subscribers)) {
                $channel_subscribers = $this->rtm_channel_subscribers[$channel_name];
            }

            //if (! $this->messageIsFromNode($data)) {
                // save data in db
                $requester_id = $this->getConnectionUserId($connection, $data);
                UpdateBroadcasterAgoraUid::dispatch($requester_id, $content_id, $broadcaster_id, $agora_uid);
            //}

            $message = [
                'event' => 'broadcaster-added-to-rtm-channel',
                'channel_name' => $channel_name,
                'broadcaster_id' => $broadcaster_id,
                'agora_uid' => $agora_uid,
            ];
            // TO DO: we might want to enforce that only broadcasters can update their UIDs
            // This means that the propagation should be handled by a job that has validated the requester
            foreach ($channel_subscribers as $key => $resourceId) {
                // make sure the user is still connected
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
                'event_name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
            Log::error($exception);
            return;
        }
    }

    private function muteRtmChannelBroadcaster($data, $connection)
    {
        try {
            $validator = Validator::make((array) $data, [
                'channel_name' => ['required', 'string'],
                'broadcaster_id' => ['required', 'string'],
                'content_id' => ['required', 'string'],
                'agora_uid' => ['required', 'string'],
                'stream' => ['required', 'string', 'in:audio,video'],
            ]);

            if ($validator->fails()) {
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event_name' => $data->event,
                    'message' => 'Invalid or missing input fields',
                    'errors' => $validator->errors()->toArray(),
                ]));
                return;
            }

            $this->propagateToOtherNodes($data, $connection);

            $content_id = $data->content_id;
            $broadcaster_id = $data->broadcaster_id;
            $agora_uid = $data->agora_uid;
            $stream = $data->stream;

            $channel_name = $data->channel_name;
            $channel_subscribers = [];
            if (array_key_exists($channel_name, $this->rtm_channel_subscribers)) {
                $channel_subscribers = $this->rtm_channel_subscribers[$channel_name];
            }

            //if (! $this->messageIsFromNode($data)) {
                // save data in db
                $requester_id = $this->getConnectionUserId($connection, $data);
                MuteRtmBroadcaster::dispatch($requester_id, $content_id, $broadcaster_id, $agora_uid, $stream);
           // }

            $message = [
                'event' => 'broadcaster-muted-in-rtm-channel',
                'channel_name' => $channel_name,
                'broadcaster_id' => $broadcaster_id,
                'agora_uid' => $agora_uid,
                'stream' => $stream,
            ];

            foreach ($channel_subscribers as $key => $resourceId) {
                // make sure the user is still connected
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
                'event_name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
            Log::error($exception);
            return;
        }
    }

    private function unmuteRtmChannelBroadcaster($data, $connection)
    {
        try {
            $validator = Validator::make((array) $data, [
                'channel_name' => ['required', 'string'],
                'broadcaster_id' => ['required', 'string'],
                'content_id' => ['required', 'string'],
                'agora_uid' => ['required', 'string'],
                'stream' => ['required', 'string', 'in:audio,video'],
            ]);

            if ($validator->fails()) {
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event_name' => $data->event,
                    'message' => 'Invalid or missing input fields',
                    'errors' => $validator->errors()->toArray(),
                ]));
                return;
            }

            $this->propagateToOtherNodes($data, $connection);

            $content_id = $data->content_id;
            $broadcaster_id = $data->broadcaster_id;
            $agora_uid = $data->agora_uid;
            $stream = $data->stream;

            $channel_name = $data->channel_name;
            $channel_subscribers = [];
            if (array_key_exists($channel_name, $this->rtm_channel_subscribers)) {
                $channel_subscribers = $this->rtm_channel_subscribers[$channel_name];
            }

            //if (! $this->messageIsFromNode($data)) {
                // save data in db
                $requester_id = $this->getConnectionUserId($connection, $data);
                UnmuteRtmBroadcaster::dispatch($requester_id, $content_id, $broadcaster_id, $agora_uid, $stream);
           // }

            $message = [
                'event' => 'broadcaster-unmuted-in-rtm-channel',
                'channel_name' => $channel_name,
                'broadcaster_id' => $broadcaster_id,
                'agora_uid' => $agora_uid,
                'stream' => $stream,
            ];

            foreach ($channel_subscribers as $key => $resourceId) {
                // make sure the user is still connected
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
                'event_name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
            Log::error($exception);
            return;
        }
    }

    private function requestBroadcastInRtmChannel($data, $connection)
    {
        try {
            $validator = Validator::make((array) $data, [
                'channel_name' => ['required', 'string'],
                'broadcaster_id' => ['required', 'string'],
                'content_id' => ['required', 'string'],
                'agora_uid' => ['required', 'string'],
                'stream' => ['required', 'string', 'in:audio,video'],
            ]);

            if ($validator->fails()) {
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event_name' => $data->event,
                    'message' => 'Invalid or missing input fields',
                    'errors' => $validator->errors()->toArray(),
                ]));
                return;
            }

            $this->propagateToOtherNodes($data, $connection);

            $content_id = $data->content_id;
            $broadcaster_id = $data->broadcaster_id;
            $agora_uid = $data->agora_uid;
            $stream = $data->stream;

            $channel_name = $data->channel_name;
            $channel_subscribers = [];
            if (array_key_exists($channel_name, $this->rtm_channel_subscribers)) {
                $channel_subscribers = $this->rtm_channel_subscribers[$channel_name];
            }

            $message = [
                'event' => 'broadcast-request-in-rtm-channel',
                'channel_name' => $channel_name,
                'broadcaster_id' => $broadcaster_id,
                'agora_uid' => $agora_uid,
                'stream' => $stream,
            ];

            foreach ($channel_subscribers as $key => $resourceId) {
                // make sure the user is still connected
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
                'event_name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
            Log::error($exception);
            return;
        }
    }

    private function updateScreenShareStatus($data, $connection)
    {
        try {
            $validator = Validator::make((array) $data, [
                'channel_name' => ['required', 'string'],
                'user_id' => ['required', 'string'],
                'status' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                $connection->send(json_encode([
                    'event' => 'event-error',
                    'event_name' => $data->event,
                    'message' => 'Invalid or missing input fields',
                    'errors' => $validator->errors()->toArray(),
                ]));
                return;
            }

            $this->propagateToOtherNodes($data, $connection);

            $user_id = $data->user_id;
            $status = $data->status;
            $channel_name = $data->channel_name;
            $channel_subscribers = [];
            if (array_key_exists($channel_name, $this->rtm_channel_subscribers)) {
                $channel_subscribers = $this->rtm_channel_subscribers[$channel_name];
            }

            $message = [
                'event' => 'screen-share-status-updated',
                'channel_name' => $channel_name,
                'status' => $status,
                'user_id' => $user_id,
            ];

            foreach ($channel_subscribers as $key => $resourceId) {
                // make sure the user is still connected
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
                'event_name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
            Log::error($exception);
            return;
        }
    }

    private function updateRtmChannelSubscribersCount($data, $connection)
    {
        try {
            // send message to channel subscribers
            $channel_name = $data->channel_name;
            $channel_subscribers = [];
            if (array_key_exists($channel_name, $this->rtm_channel_subscribers)) {
                $channel_subscribers = $this->rtm_channel_subscribers[$channel_name];
            }
            $this->propagateToOtherNodes($data, $connection);
            $message = [
                'event' => 'update-rtm-channel-subscribers-count',
                'channel_name' => $channel_name,
                'subscribers_count' => $data->subscribers_count,
            ];
            foreach ($channel_subscribers as $key => $resourceId) {
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
                'event_name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
            Log::error($exception);
            return;
        }
    }

    private function signOutOtherDevices($data, $connection)
    {
        try {
            // send message to channel subscribers
            $channel_name = $data->channel_name;
            $channel_subscribers = [];
            if (array_key_exists($channel_name, $this->rtm_channel_subscribers)) {
                $channel_subscribers = $this->rtm_channel_subscribers[$channel_name];
            }
            $this->propagateToOtherNodes($data, $connection);
            $message = [
                'event' => 'sign-out-other-devices',
                'channel_name' => $channel_name,
                'access_token' => $data->access_token,
                'device_token' => $data->device_token,
            ];
            foreach ($channel_subscribers as $key => $resourceId) {
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
                'event_name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
            Log::error($exception);
            return;
        }
    }

    private function updateNumberOfTipsForContent($data, $connection)
    {
        try {
            // // send message to channel subscribers
            // $channel_name = $data->channel_name;
            // $channel_subscribers = [];
            // if (array_key_exists($channel_name, $this->rtm_channel_subscribers)) {
            //     $channel_subscribers = $this->rtm_channel_subscribers[$channel_name];
            // }
            $this->propagateToOtherNodes($data, $connection);
            $message = [
                'event' => 'update-number-of-tips-for-content',
                'content_id' => $data->content_id,
                'tips_count' => $data->tips_count,
            ];
            foreach ($channel_subscribers as $key => $resourceId) {
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
                'event_name' => $data->event,
                'message' => 'Oops, an error occurred, please try again later',
                'errors' => [],
            ]));
            Log::error($exception);
            return;
        }
    }

    private function checkConnectionIsAuthenticated($connection, $data)
    {
        $requester_id = $this->getConnectionUserId($connection, $data);
        if (is_null($requester_id) || $requester_id == '') {
            return false;
        }
        return true;
    }

    private function messageIsFromNodeOrApp($data)
    {
        if (isset($data->source_type) && ($data->source_type === 'ws-node' || $data->source_type === 'app')) {
            return true;
        }
        return false;
    }

    private function messageIsFromNode($data)
    {
        if (isset($data->source_type) && $data->source_type === 'ws-node') {
            return true;
        }
        return false;
    }

    private function getConnectionUserId($connection, $data = null)
    {
        try {
            $authorization = [];
            foreach ($connection->httpRequest->getHeaders() as $key => $value) {
                $key = strtolower($key);
                if ($key === 'authorization') {
                    $authorization = $value;
                    break;
                } else if ($key === 'cookie') {
                    $value = $value[0];
                    $cookies = explode(";", $value);
                    foreach ($cookies as $cookey => $coovalue) {
                        if (str_contains(strtolower($coovalue), 'authorization=')) {
                            $auth_parts = explode("=", $coovalue);
                            $authorization[0] = urldecode($auth_parts[1]);
                            break;
                        }
                    }
                    
                }
            }

            if (empty($authorization) || $authorization[0] == '' || is_null($authorization[0])) {
                return;
            }

            $token = explode(' ', $authorization[0])[1];
            JWTAuth::setToken($token);

            if (! $claim = JWTAuth::getPayload()) {
                return '';
            }

            return $claim['sub'];
        } catch (\Exception $exception) {
            $connection->send(json_encode([
                'event' => 'event-error',
                'event_name' => 'get-connection-user-id',
                'message' => $exception->getMessage(),
                'errors' => [],
            ]));
            Log::error($exception);
            Log::info("WS Identity: " . $this->ws_identity);
            Log::info(json_encode($data));
            return '';
        }
    }

    private function propagateToOtherNodes($data, $connection)
    {
        $data_source_type = null;
        $data_source_id = null;
        if (isset($data->source_type)) {
            $data_source_type = $data->source_type;
        }
        if (isset($data->source_id)) {
            $data_source_id = $data->source_id;
        }

        if ($data_source_type !== 'ws-node') {
            $connection_token = '';
            $authorization = [];
            foreach ($connection->httpRequest->getHeaders() as $key => $value) {
                $key = strtolower($key);
                if ($key === 'authorization') {
                    $authorization = $value;
                }
            }
            if (! empty($authorization)) {
                $connection_token = $authorization[0];
            }
            
            $data->source_type = 'ws-node';
            $data->source_id = $this->ws_identity;
            $data->connection_token = $connection_token;
            Redis::publish(Constants::WEBSOCKET_MESSAGE_CHANNEL, json_encode($data));
        }
    }
}
