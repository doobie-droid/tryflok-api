<?php

namespace App\Jobs\Content;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifySubscriber implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $content;
    public $subscriber;
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

        $client = new Client;
        $url = 'https://fcm.googleapis.com/fcm/send';
        $authorization_key = config('services.google.fcm_server_key');
        foreach ($this->subscriber->notificationTokens as $notification_token) {
            $client->post($url, [
                'headers' => [
                    'Authorization' => "key={$authorization_key}",
                ],
                'json' => [
                    'to' => $notification_token->token,
                    'notification' => [
                        'title' => 'Newsletter Release',
                        'body' => $message,
                    ],
                    'data' => [
                        'message' => $message,
                        'notificable_type' => 'content',
                        'notificable_id' => $this->content->id,
                    ],
                ],
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
