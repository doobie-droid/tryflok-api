<?php

namespace App\Mail\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendReferrerMail extends Mailable
{
    use Queueable, SerializesModels;
    public $message;
    public $referrer;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->message = $data['message'];
        $this->referrer = $data['referrer'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.user.referrer')->with([
            'referrer_message' => $this->message,
            'referrer' => $this->referrer,
        ])->subject('New sign up on flok!');
    }
}
