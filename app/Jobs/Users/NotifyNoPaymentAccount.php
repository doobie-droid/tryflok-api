<?php

namespace App\Jobs\Users;

use App\Http\Resources\NotificationResource;
use App\Mail\User\NoPaymentAccountMail;
use App\Models\Notification;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyNoPaymentAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $payout;
    public $user;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->payout = $data['payout'];
        $this->user = $data['user'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = "We tried paying out USD {$this->payout->amount} to you but were unable to because you have no payment account. Please add a payment account and you would recieve your payout whithin the next 24hrs";

        $notification = $this->user->notifications()->create([
            'notifier_id' => $this->user->id,
            'message' => $message,
            'notificable_type' => 'payout',
            'notificable_id' => $this->payout->id,
        ]);

        $notification = Notification::with('notifier', 'notifier.profile_picture', 'notificable')->where('id', $notification->id)->first();
        $notification = new NotificationResource($notification);
        $image = 'https://res.cloudinary.com/akiddie/image/upload/v1639156702/flok-logo.png';
        if (! is_null($this->user->profile_picture()->first())) {
            $image = $this->user->profile_picture()->first()->url;
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
                        'title' => 'Payout Failed',
                        'body' => $message,
                        'image' => $image,
                    ],
                    'data' => $notification,
                ],
            ]);
        }

        Mail::to($this->user)->send(new NoPaymentAccountMail([
            'user' => $this->user,
            'message' => $message,
        ]));
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
