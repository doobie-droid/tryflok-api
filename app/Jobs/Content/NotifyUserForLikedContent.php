<?php

namespace App\Jobs\Content;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use GuzzleHttp\Client;

class NotifyUserForLikedContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $content;
    public $liker;
    public $likee;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->liker = $data['liker'];
        $this->content = $data['content'];
        $this->likee = $data['likee'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = "@{$this->liker->username} just liked your content: {$this->content->title}";
        $notification = $this->likee->notifications()->create([
            'notifier_id' => $this->liker->id,
            'message' => $message,
            'notificable_type' => 'content',
            'notificable_id' => $this->content->id,
        ]);
        $notification = Notification::with('notifier', 'notifier.profile_picture', 'notificable')->where('id', $notification->id)->first();
        $notification = new NotificationResource($notification);
        $image = 'https://res.cloudinary.com/akiddie/image/upload/v1639156702/flok-logo.png';
        if (! is_null($this->liker->profile_picture()->first())) {
            $image = $this->liker->profile_picture()->first()->url;
        }

        // send push notification
        $client = new Client;
        $url = 'https://fcm.googleapis.com/fcm/send';
        $authorization_key = config('services.google.fcm_server_key');
        foreach ($this->likee->notificationTokens as $notification_token) {
            $client->post($url, [
                'headers' => [
                    'Authorization' => "key={$authorization_key}",
                ],
                'json' => [
                    'to' => $notification_token->token,
                    'notification' => [
                        'title' => 'Your content has been liked!',
                        'body' => $message,
                        'image' => $image,
                    ],
                    'data' => $notification,
                ],
            ]);
        }
    }
    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
