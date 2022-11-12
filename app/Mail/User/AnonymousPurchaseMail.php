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
    public $access_tokens;
    public $name;
    public $content_url;
    public $decryted_pdf;
    public $pdf_status;
    public $pdf_message;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->message = $data['message'];
        $this->access_tokens = $data['access_tokens'];
        $this->name = $data['name'];
        $this->content_url = $data['content_url'];
        $this->decrypted_pdf = $data['decrypted_pdf'];
        $this->pdf_status = $data['pdf_status'];
        $this->pdf_message = $data['pdf_message'];
     }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if ($this->decrypted_pdf == '') {
            return $this->view('emails.user.content.anonymous-content-purchase')->with([
                'contents' => $this->message,
                'access_tokens' => $this->access_tokens,
                'name' => $this->name,
                'content_url' => $this->content_url,
                'pdf_status' => $this->pdf_status,
                'pdf_message' => $this->pdf_message,
            ])->subject('Your Flok Purchase Has Arrived!');
        }
        return $this->view('emails.user.content.anonymous-content-purchase')->with([
            'contents' => $this->message,
            'access_tokens' => $this->access_tokens,
            'name' => $this->name,
            'content_url' => $this->content_url,
            'pdf_status' => $this->pdf_status,
            'pdf_message' => $this->pdf_message,
        ])->subject('Your Flok Purchase Has Arrived!')
          ->attachData($this->decrypted_pdf, 'file.pdf');
    }
}
