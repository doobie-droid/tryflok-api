<?php

namespace App\Jobs\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\User\SendReferralMail;
use Illuminate\Support\Facades\Mail;
use App\Utils\RestResponse;

class SendReferralEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $email;
    public $referrer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->email = $data['email'];
        $this->referrer = $data['referrer'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = "{$this->referrer->name} (@{$this->referrer->username}) referred you to sign up on flok";
            Mail::to($this->email)->send(new SendReferralMail([
            'referrer_id' => $this->referrer->id,
            'message' => $message,
        ]));
    }
}
