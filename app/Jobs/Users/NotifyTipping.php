<?php

namespace App\Jobs\Users;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyTipping implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tipper;
    public $tippee;
    public $amount_in_flk;
    public $revenue;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->tipper = $data['tipper'];
        $this->tippee = $data['tippee'];
        $this->amount_in_flk = $data['amount_in_flk'];
        $this->revenue = $data['revenue'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = "@{$this->tipper->username} just gifted you {$this->amount_in_flk} Flok Cowries";
        // TO DO: send push notification to user
        $this->tippee->notifications()->create([
            'message' => $message,
            'notificable_type' => 'revenue',
            'notificable_id' => $this->revenue->id,
        ]);
        // send push notification
        $client = new Client;
        $url = 'https://fcm.googleapis.com/fcm/send';
        $authorization_key = config('services.google.fcm_server_key');
        foreach ($this->tippee->notificationTokens as $notification_token) {
            $client->post($url, [
                'headers' => [
                    'Authorization' => "key={$authorization_key}",
                ],
                'json' => [
                    'to' => $notification_token->token,
                    'notification' => [
                        'title' => 'You just got tipped!',
                        'body' => $message,
                    ],
                    'data' => [
                        'message' => $message,
                        'notificable_type' => 'revenue',
                        'notificable_id' => $this->revenue->id,
                    ],
                ],
            ]);
        }
        // TO DO: mail the user they have been tipped
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
