<?php

namespace App\Jobs\Content;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyFollower implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $message, $notificable_type, $notificable_id, $follower;
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
        // TO DO: do websocket and firebase push notifications
        $this->follower->notifications()->create([
            'message' => $this->message,
            'notificable_type' => $this->notificable_type,
            'notificable_id' => $this->notificable_id,
        ]);
    }
}
