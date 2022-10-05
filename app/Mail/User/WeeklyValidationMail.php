<?php

namespace App\Mail\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WeeklyValidationMail extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public $message;
    public $start_of_week;
    public $end_of_week;
    public $analytics_percentages;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->user = $data['user'];
        $this->message = $data['message'];
        $this->start_of_week = $data['start_of_week'];
        $this->end_of_week = $data['end_of_week'];
        $this->analytics_percentages = $data['analytics_percentages'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.user.validation.creator-weekly-validation')->with([
            'user' => $this->user,
            'contents' => $this->message,
            'start_of_week' => $this->start_of_week,
            'end_of_week' => $this->end_of_week,
            'analytics_percentages' => $this->analytics_percentages,
        ])->subject('Weekly Flokdate!');
    }
}