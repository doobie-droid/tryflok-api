<?php

namespace App\Mail\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExternalCommunityMail extends Mailable
{
    use Queueable, SerializesModels;
    public $email;
    public $message;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->email = $data['email'];
        $this->message = $data['message'];
        $this->content_id = $data['content_id'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.user.content.new-content-upload')->with([
            'email' => $this->email,
            'content_message' => $this->message,
            'content_id' => $this->content_id,
        ])->subject('New Content Upload!');
    }
}
