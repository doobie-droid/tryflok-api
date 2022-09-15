<?php

namespace App\Mail\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendReferralMail extends Mailable
{
    use Queueable, SerializesModels;
    public $referrer;
    public $message;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->referrer_id = $data['referrer_id'];
        $this->message = $data['message'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.user.referral')->with([
            'referrer_id' => $this->referrer_id,
            'referral_message' => $this->message,
        ])->subject('Referral to sign up!');
    }
}
