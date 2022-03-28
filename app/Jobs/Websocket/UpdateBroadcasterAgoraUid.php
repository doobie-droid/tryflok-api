<?php

namespace App\Jobs\Websocket;

use App\Models\Content;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateBroadcasterAgoraUid implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $requester_id;
    public $content_id;
    public $broadcaster_id;
    public $agora_uid;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($requester_id, $content_id, $broadcaster_id, $agora_uid)
    {
        $this->requester_id = $requester_id;
        $this->content_id = $content_id;
        $this->broadcaster_id = $broadcaster_id;
        $this->agora_uid = $agora_uid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // TO DO: we might want to enforce that only broadcasters can update their UIDs
        $content = Content::where('id', $this->content_id)->first();
        if (is_null($content)) {
            return;
        }

        $broadcaster = $content->liveBroadcasters()->where('user_id', $this->broadcaster_id)->first();

        if (is_null($broadcaster)) {
            return;
        }

        $broadcaster->agora_uid = $this->agora_uid;
        $broadcaster->video_stream_status = 'broadcasting';
        $broadcaster->audio_stream_status = 'broadcasting';
        $broadcaster->save();
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
