<?php

namespace App\Mail\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FailedTipMail extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public $message;
    public $email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->user = $data['user'];
        $this->message = $data['message'];
        $this->email = $data['email'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.user.user-tip-unsuccessful')->with([
            'user' => $this->user,
            'failure_message' => $this->message,
            'email' => $this->email,
        ])->subject('Tip User Failed!');
    }
}
