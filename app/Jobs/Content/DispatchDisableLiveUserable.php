<?php

namespace App\Jobs\Content;

use App\Jobs\Content\DisableLiveUserable as DisableLiveUserableJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchDisableLiveUserable implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $live_content;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->live_content = $data['live_content'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->live_content->userables()->chunk(100000, function ($userables) {
            foreach ($userables as $userable) {
                DisableLiveUserableJob::dispatch([
                    'userable' => $userable,
                ]);
            }
        });
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
