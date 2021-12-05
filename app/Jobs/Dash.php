<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Streaming\FFMpeg;

class Dash implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $resource_path;
    public $timeout = 60;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->resource_path = $data['resource_path'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $ffmpeg =  FFMpeg::create();
       /* $ffmpegCore = FFMpegCore::create();
        $admin_resource = $ffmpegCore->open($this->resource_path);
        $admin_resource
        ->filters()
        ->watermark(public_path() . '/creator-wm.png', array(
            'position' => 'relative',
            'bottom' => 50,
            'right' => 50,
        ));
        $admin_resource->save(new \FFMpeg\Format\Video\X264(), public_path() . '/upload/admin.mp4');*/


        $resource = $ffmpeg->open($this->resource_path);

        $hlsenc = $resource->hls()
        ->encryption(join_path(public_path(), "hls-encrypted/user-key"), join_path(env('BACKEND_URL'), "hls-encrypted/user-key"))
        ->setHlsTime(30)
        ->setHlsBaseUrl(join_path(env('BACKEND_URL'), 'hls-encrypted'))
        ->x264()
        ->autoGenerateRepresentations([480, 720, 1080, ]);
        $hlsenc->save(join_path(public_path(), "hls-encrypted/hls.m3u8"));

       /* //generate admin version
        $admin_video = $ffmpeg->open(public_path() . '/upload/admin.mp4');
        $admhlsenc = $admin_video->hls()
        ->encryption(public_path() . "/hls-encrypted/creator-key", env('BACKEND_URL') . "hls-encrypted/creator-key")
        ->setHlsTime(30)
        ->x264()
        ->autoGenerateRepresentations([2160]);
        $admhlsenc->save(public_path() . "/hls-encrypted/hls-creator.m3u8");*/
        //it does not generate versions greater than the dimensions of the video itself
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
