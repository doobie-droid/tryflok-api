<?php

namespace App\Jobs\Users;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\Users\EmailAnonymousUsers as EmailAnonymousUsersJob;
use App\Models\Collection;
use App\Models\Content;

class NotifyAnonymousUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $anonymousPurchase;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($anonymousPurchase)
    {
        $this->anonymousPurchase = $anonymousPurchase;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            switch ($this->anonymousPurchase['anonymous_purchaseable_type']) {
                case 'content':
                    $content = Content::where('id', $this->anonymousPurchase['anonymous_purchaseable_id'])->where('live_status', 'active')->first();
                    if (! is_null($content) && ($content->type == 'live-audio' || $content->type == 'live-video'))
                    {
                        EmailAnonymousUsersJob::dispatch([
                            'content' => $content,
                            'user_email' => $this->anonymousPurchase->email,
                        ]);
                    }
                    break;
                case 'collection':
                    $collection = Collection::where('id', $this->anonymousPurchase['anonymous_purchaseable_id'])->first();
                    $contents = $collection->contents()->where('live_status', 'active')
                    ->where(function ($query) {
                        $query->where('type', 'live-video')
                                ->orWhere('type', 'live-audio');
                    })->get();
                        foreach ($contents as $content)
                        {
                            EmailAnonymousUsersJob::dispatch([
                                'content' => $content,
                                'user_email' => $this->anonymousPurchase->email,
                            ]); 
                        }
                    break;
            }

        } catch (\Exception $exception) {
            throw $exception;
            Log::error($exception);
        }
    }
}
