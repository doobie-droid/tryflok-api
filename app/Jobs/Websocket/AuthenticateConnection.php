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
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->headers = $data['headers'];
        $this->resource_id = $data['resource_id'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! array_key_exists('Authorization', $this->headers)) {
            return;
        }

        $token = explode(' ', $this->headers['Authorization'][0])[1];
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
        ]));
        $websocket_client->close();
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
