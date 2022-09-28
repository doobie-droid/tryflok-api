<?php

namespace App\Jobs\Content;

use App\Jobs\Content\NotifyFollower as NotifyFollowerJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchNotificationToFollowers implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $notificable_type;
    public $notificable_id;
    public $user;
    public $message;
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
        $this->user = $data['user'];
        $this->notifier = $data['notifier'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = $this->message;
        $notifier = $this->notifier;
        $notificable_type = $this->notificable_type;
        $notificable_id = $this->notificable_id;

        $this->user->followers()->chunk(100000, function ($users) use ($message, $notificable_type, $notificable_id, $notifier) {
            foreach ($users as $user) {
                NotifyFollowerJob::dispatch([
                    'message' => $message,
                    'notificable_type' => $notificable_type,
                    'notificable_id' => $notificable_id,
                    'follower' => $user,
                    'notifier' => $notifier,
                ]);
            }
        });
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
