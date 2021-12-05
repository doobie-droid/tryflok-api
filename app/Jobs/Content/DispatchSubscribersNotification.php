<?php

namespace App\Jobs\Content;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchSubscribersNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $content;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->content = $data['content'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $content = $this->content;
        $this->content->subscribers()->chunk(100000, function ($users) use ($content) {
            foreach ($users as $user) {
                NotifiySubscriberJob::dispatch([
                    'subscriber' => $user,
                    'content' => $content,
                ]);
            }
        });
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
