<?php

namespace App\Jobs\Websocket;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticateConnection implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $headers;
    public $resource_id;
    public $ws_identity;
    public $cookies;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->headers = $data['headers'];
        $this->resource_id = $data['resource_id'];
        $this->ws_identity = $data['ws_identity'];
        $this->onConnection('redis_local');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $authorization = [];
        foreach ($this->headers as $key => $value) {
            $key = strtolower($key);
            if ($key === 'authorization') {
                $authorization = $value;
                break;
            } else if ($key === 'cookie') {
                Log::info($value);
                $value = $value[0];
                Log::info($value);
                $cookies = explode(";", $value);
                Log::info($cookies);
                foreach ($cookies as $cookey => $coovalue) {
                    Log::info(json_encode("{$cookey}: {$coovalue}"));
                    $cookey = strtolower($cookey);
                    if ($cookey === 'authorization') {
                        $authorization[0] = $coovalue;
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
            return;
        }

        $user_id = $claim['sub'];
        $user = User::where('id', $user_id)->first();

        if (is_null($user)) {
            return;
        }

        $profile_picture = '';
        if (! is_null($user->profile_picture()->first())) {
            $profile_picture = $user->profile_picture()->first()->url;
        }

        $websocket_client = new \WebSocket\Client(config('services.websocket.url'));
        $websocket_client->text(json_encode([
            'event' => 'app-set-connection-as-authenticated',
            'user_id' => $user->id,
            'username' => $user->username,
            'profile_picture' => $profile_picture,
            'resource_id' => $this->resource_id,
            'ws_identity' => $this->ws_identity,
            'source_type' => 'app',
        ]));
        $websocket_client->close();
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
