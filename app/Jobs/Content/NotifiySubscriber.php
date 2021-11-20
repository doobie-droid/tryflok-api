<?php

namespace App\Jobs\Content;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifiySubscriber implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $content, $subscriber;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->content = $data['content'];
        $this->subscriber = $data['subscriber'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->subscriber->notifications()->create([
            'message' => "A new issue has been released for {$this->content->title}",
            'notificable_type' => 'content',
            'notificable_id' => $this->content->id,
        ]);
        //send out push notification too
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
