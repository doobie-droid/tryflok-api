<?php

namespace App\Mail\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExportExternalCommunityMail extends Mailable
{
    use Queueable, SerializesModels;
    private $user;
    private $message;
    private $file;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->user = $data['user'];
        $this->message = $data['message'];
        $this->file = $data['file'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.user.export-external-community')->with([
            'user' => $this->user,
            'content_message' => $this->message,
        ])->subject('External Community Export!')
        ->attach($this->file, [
            'as' => "{$this->user->username}-external-community.csv"
        ]);
    }
}
