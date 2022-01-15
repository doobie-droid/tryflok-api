<?php

namespace App\Jobs\Users;

use App\Http\Resources\NotificationResource;
use App\Mail\User\SaleMade;
use App\Models\Notification;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifySale implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $owner;
    private $item;
    private $item_type;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($owner, $item, $item_type)
    {
        $this->owner = $owner;
        $this->item = $item;
        $this->item_type = $item_type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = "Your {$this->item_type} titled {$this->item->title} just got purchased for {$this->item->prices()->first()->amount}!";

        $notification = $this->owner->notifications()->create([
            'notifier_id' => $this->owner->id,
            'message' => $message,
            'notificable_type' => $this->item_type,
            'notificable_id' => $this->item->id,
        ]);

        $notification = Notification::with('notifier', 'notifier.profile_picture', 'notificable')->where('id', $notification->id)->first();
        $notification = new NotificationResource($notification);
        $image = 'https://res.cloudinary.com/akiddie/image/upload/v1639156702/flok-logo.png';
        if (! is_null($this->item->cover()->first())) {
            $image = $this->item->cover()->first()->url;
        }

        $client = new Client;
        $url = 'https://fcm.googleapis.com/fcm/send';
        $authorization_key = config('services.google.fcm_server_key');
        foreach ($this->owner->notificationTokens as $notification_token) {
            $client->post($url, [
                'headers' => [
                    'Authorization' => "key={$authorization_key}",
                ],
                'json' => [
                    'to' => $notification_token->token,
                    'notification' => [
                        'title' => 'You Just Made A Sale!',
                        'body' => $message,
                        'image' => $image,
                    ],
                    'data' => $notification,
                ],
            ]);
        }

        Mail::to($this->owner)->send(new SaleMade([
            'user' => $this->owner,
            'message' => $message,
        ]));
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
