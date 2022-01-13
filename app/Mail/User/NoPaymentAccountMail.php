<?php

namespace App\Mail\User;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NoPaymentAccountMail extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public $message;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->user = $data['user'];
        $this->message = $data['message'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.user.no-payment-account')->with([
            'user' => $this->user,
            'payout_message' => $this->message,
        ])->subject('Payout Failed');
    }
}
