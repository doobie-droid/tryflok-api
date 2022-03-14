<?php

namespace App\Jobs\Websocket;

use App\Models\Content;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;

class UpdateContestantAgoraUid implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $content_id;
    public $contestant_id;
    public $agora_uid;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($content_id, $contestant_id, $agora_uid)
    {
        $this->content_id = $content_id;
        $this->contestant_id = $contestant_id;
        $this->agora_uid = $agora_uid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $content = Content::where('id', $this->content_id)->first();
        if (is_null($content)) {
            return;
        }

        $contestant = $contestant = $content->challengeContestants()->where('user_id', $this->contestant_id)->first();

        if (is_null($contestant)) {
            return;
        }

        $contestant->agora_uid = $this->agora_uid;
        $contestant->save();
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
