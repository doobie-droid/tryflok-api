<?php

namespace App\Mail\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AnonymousPurchaseMail extends Mailable
{
    use Queueable, SerializesModels;
    public $message;
    public $access_token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->message = $data['message'];
        $this->access_tokens = $data['access_tokens'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.user.content.anonymous-content-purchase')->with([
            'contents' => $this->message,
            'access_tokens' => $this->access_tokens,
        ])->subject('Anonymous Purchase on Flok!');
    }
}
