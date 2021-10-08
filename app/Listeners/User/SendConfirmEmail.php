<?php

namespace App\Listeners\User;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\User\EmailConfirmation;
use App\Events\User\ConfirmEmail as ConfirmEmailEvent;
use Illuminate\Support\Facades\Log;

class SendConfirmEmail implements ShouldQueue
{
    public $tries = 3;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ConfirmEmailEvent $event)
    {
        try {
            $event->user->notify(new EmailConfirmation($event->user));
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
        
    }
}
