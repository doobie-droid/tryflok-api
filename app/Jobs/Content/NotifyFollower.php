<?php

namespace App\Jobs\Content;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use \GuzzleHttp\Client;

class NotifyFollower implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $message, $notificable_type, $notificable_id, $follower;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->message = $data['message'];
        $this->notificable_type = $data['notificable_type'];
        $this->notificable_id = $data['notificable_id'];
        $this->follower = $data['follower'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->follower->notifications()->create([
            'message' => $this->message,
            'notificable_type' => $this->notificable_type,
            'notificable_id' => $this->notificable_id,
        ]);

        $client = new Client();
        $url = "https://fcm.googleapis.com/fcm/send";
        $authorization_key = env('FCM_SERVER_KEY');
        foreach ($this->follower->notificationTokens as $notification_token) {
            $client->post($url,  [
                'headers' => [
                    'Authorization' => "key={$authorization_key}",
                ],
                'json' => [
                    "token" => $notification_token->token,
                    "notification" => [
                        "title" => 'New Content From Creator',
                        "body" => $this->message,
                    ],
                    "data" => [
                        "message" => $this->message,
                        "notificable_type" => $this->notificable_type,
                        "notificable_id" => $this->notificable_id,
                    ]
                ],
            ]);
        }
    }
}
