<?php

namespace App\Jobs\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Notification;
use App\Http\Resources\NotificationResource;

class NotifyFollow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $follower;
    public $user;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->follower = $data['follower'];
        $this->user = $data['user'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = "@{$this->follower->username} followed you";
        $notification = $this->follower->notifications()->create([
            'notifier' => $this->follower->id,
            'message' => $message,
            'notificable_type' => 'user',
            'notificable_id' => $this->follower->id,
        ]);
        $notification = Notification::with('notifier', 'notifier.profile_picture', 'notificable')->where('id', $notificaton->id)->first();
        $image = 'https://res.cloudinary.com/akiddie/image/upload/v1639156702/flok-logo.png';
        if (! is_null($this->notifier->profile_picture()->first())) {
            $image = $this->notifier->profile_picture()->first()->url;
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
                        'title' => 'New Follower',
                        'body' => $message,
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
