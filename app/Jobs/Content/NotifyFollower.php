<?php

namespace App\Jobs\Content;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
    public $notifier;
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
        $this->notifier = $data['notifier'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $notification = $this->follower->notifications()->create([
            'notifier' => $this->notifier,
            'message' => $this->message,
            'notificable_type' => $this->notificable_type,
            'notificable_id' => $this->notificable_id,
        ]);
        $notification = Notification::with('notifier', 'notifier.profile_picture', 'notificable')->where('id', $notificaton->id)->first();

        $image = 'https://res.cloudinary.com/akiddie/image/upload/v1639156702/flok-logo.png';
        if (in_array($this->notificable_type, ['collection', 'content']) 
            && 
            ! is_null($notification->notificable()->first()->cover()->first())
        ) {
            $image = $notification->notificable()->first()->cover()->first()->url;
        }

        $client = new Client;
        $url = 'https://fcm.googleapis.com/fcm/send';
        $authorization_key = config('services.google.fcm_server_key');
        foreach ($this->follower->notificationTokens as $notification_token) {
            $client->post($url, [
                'headers' => [
                    'Authorization' => "key={$authorization_key}",
                ],
                'json' => [
                    'to' => $notification_token->token,
                    'notification' => [
                        'title' => 'New Content Available',
                        'body' => $this->message,
                        'image' => $image,
                    ],
                    'data' => new NotificationResource($notification),
                ],
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
