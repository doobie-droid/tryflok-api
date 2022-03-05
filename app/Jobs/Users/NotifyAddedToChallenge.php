<?php

namespace App\Jobs\Users;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Content;

class NotifyAddedToChallenge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $user;
    private $content;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, Content $content)
    {
        $this->user = $user;
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = "You have been added as a contestant to the {$this->content->title} challenge. You can choose to accept or decline";

        $notification = $this->user->notifications()->create([
            'notifier_id' => $this->content->user_id,
            'message' => $message,
            'notificable_type' => 'content',
            'notificable_id' => $this->content->id,
        ]);

        $notification = Notification::with('notifier', 'notifier.profile_picture', 'notificable')->where('id', $notification->id)->first();
        $notification = new NotificationResource($notification);
        $image = 'https://res.cloudinary.com/akiddie/image/upload/v1639156702/flok-logo.png';
        if (! is_null($this->content->cover()->first())) {
            $image = $this->content->cover()->first()->url;
        }

        $client = new Client;
        $url = 'https://fcm.googleapis.com/fcm/send';
        $authorization_key = config('services.google.fcm_server_key');
        foreach ($this->user->notificationTokens as $notification_token) {
            $client->post($url, [
                'headers' => [
                    'Authorization' => "key={$authorization_key}",
                ],
                'json' => [
                    'to' => $notification_token->token,
                    'notification' => [
                        'title' => 'You have been challenged',
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
