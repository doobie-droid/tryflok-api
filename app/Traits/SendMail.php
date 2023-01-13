<?php

namespace App\Traits;

use Illuminate\Support\Facades\Mail;
use App\Mail\User\YoutubeMigrateMail;

trait SendMail
{
    public function sendMail()
    {
        $message = "Sorry, there was a problem encountered while adding your podcasts. {$this->podcastError} ";
        Mail::to($this->user)->send(new YoutubeMigrateMail([
            'user' => $this->user,
            'message' => $message,
        ]));
    }
}
