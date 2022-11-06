<?php

namespace App\Jobs\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Mail\User\AnonymousLiveEvent;
use Illuminate\Support\Facades\Mail;

class EmailAnonymousUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $content;
    private $user_email;
    public $name;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->content = $data['content'];
        $this->user_email = $data['user_email'];
        $this->name = $data['name'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $scheduled_date = $this->content->scheduled_date;
            $message = '';
            if ($scheduled_date->format('Y-m-d H') == now()->addHours(3)->format('Y-m-d H'))
            {
                Log::info("LIVE EVENT: ".$this->content->title." begins in 3 hours");
                $message = "The live event you purchased on flok '{$this->content->title}' will begin in three(3) hours";
            }
            if ($scheduled_date->format('Y-m-d H') == now()->addHours(1)->format('Y-m-d H'))
            {
                Log::info("LIVE EVENT: ".$this->content->title." begins in 1 hour");
                $message = "The live event you purchased on flok '{$this->content->title}' will begin in one(1) hour";
            }
            if ($message != '') {
                Mail::to($this->user_email)->send(new AnonymousLiveEvent([
                    'message' => $message,
                    'name' => $this->name
                ]));
            }

        } catch (\Exception $exception) {
            throw $exception;
            Log::error($exception);
        }
    }
}
