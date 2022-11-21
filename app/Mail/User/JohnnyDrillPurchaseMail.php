<?php

namespace App\Mail\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JohnnyDrillPurchaseMail extends Mailable
{
    use Queueable, SerializesModels;
    public $access_tokens;
    public $avatar_url;
    public $sales_count;
    public $name;
    public $content_url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->access_tokens = $data['access_tokens'];
        $this->avatar_url = $data['avatar_url'];
        $this->sales_count = $data['sales_count'];
        $this->name = $data['name'];
        $this->content_url = $data['content_url'];
     }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.user.content.johnny-drill-content-purchase')->with([
            'access_tokens' => $this->access_tokens,
            'sales_count' => $this->sales_count,
            'avatar_url' => $this->avatar_url,
            'name' => $this->name,
            'content_url' => $this->content_url,
        ])->subject('Anonymous Purchase on Flok!');
    }
}
