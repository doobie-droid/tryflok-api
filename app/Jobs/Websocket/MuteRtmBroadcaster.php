<?php

namespace App\Jobs\Websocket;

use App\Models\Content;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MuteRtmBroadcaster implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $requester_id;
    public $content_id;
    public $broadcaster_id;
    public $agora_uid;
    public $stream;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($requester_id, $content_id, $broadcaster_id, $agora_uid, $stream)
    {
        $this->requester_id = $requester_id;
        $this->content_id = $content_id;
        $this->broadcaster_id = $broadcaster_id;
        $this->agora_uid = $agora_uid;
        $this->stream = $stream;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // TO DO: ensure only hosts or broadcasters themselves can mute

        $content = Content::where('id', $this->content_id)->first();
        if (is_null($content)) {
            return;
        }

        $broadcaster = $content->liveBroadcasters()->where('user_id', $this->broadcaster_id)->first();

        if (is_null($broadcaster)) {
            return;
        }

        switch ($this->stream) {
            case 'audio':
                $broadcaster->audio_stream_status = 'muted';
                break;
            case 'video':
                $broadcaster->video_stream_status = 'muted';
                break;
        }

        $broadcaster->save();
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
