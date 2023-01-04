<?php

namespace App\Jobs\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\User\ExternalCommunityMail;

class NotifyExternalCommunity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $user;
    private $content;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->user = $data['user'];
        $this->content = $data['content'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $externalCommunities = $this->user->externalCommunities()->get();
            foreach ($externalCommunities as $externalCommunity)
            {
                $message = "{$this->user->username} has just uploaded a {$this->content->type} titled {$this->content->title}";
                Mail::to($externalCommunity->email)->send(new ExternalCommunityMail([
                'email' => $externalCommunity->email,
                'message' => $message,
                ]));
            }

        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
}
