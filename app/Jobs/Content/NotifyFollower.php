<?php

namespace App\Jobs\Content;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyFollower implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $message;
    public $notificable_type;
    public $notificable_id;
    public $follower;
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
        $url = 'https://fcm.googleapis.com/fcm/send';
        $authorization_key = env('FCM_SERVER_KEY');
        foreach ($this->follower->notificationTokens as $notification_token) {
            $client->post($url, [
                'headers' => [
                    'Authorization' => "key={$authorization_key}",
                ],
                'json' => [
                    'to' => $notification_token->token,
                    'notification' => [
                        'title' => 'New Content From Creator',
                        'body' => $this->message,
                    ],
                    'data' => [
                        'message' => $this->message,
                        'notificable_type' => $this->notificable_type,
                        'notificable_id' => $this->notificable_id,
                    ]
                ],
            ]);
        }
    }
}
