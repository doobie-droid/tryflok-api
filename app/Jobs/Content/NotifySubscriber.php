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

class NotifySubscriber implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $content, $subscriber;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->content = $data['content'];
        $this->subscriber = $data['subscriber'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = "A new issue has been released for {$this->content->title}";
        $this->subscriber->notifications()->create([
            'message' => $message,
            'notificable_type' => 'content',
            'notificable_id' => $this->content->id,
        ]);

        $client = new Client();
        $url = "https://fcm.googleapis.com/fcm/send";
        $authorization_key = env('FCM_SERVER_KEY');
        foreach ($this->subscriber->notificationTokens as $notification_token) {
            $client->post($url,  [
                'headers' => [
                    'Authorization' => "key={$authorization_key}",
                ],
                'json' => [
                    "to" => $notification_token->token,
                    "notification" => [
                        "title" => 'Newsletter Release',
                        "body" => $message,
                    ],
                    "data" => [
                        "message" => $message,
                        "notificable_type" => 'content',
                        "notificable_id" => $this->content->id,
                    ],
                ]
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
