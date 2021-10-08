<?php

namespace App\Jobs\Content\GenerateResolutions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Content\UploadResource\Cover as UploadCoverJob;
use Illuminate\Support\Facades\Log;

class Cover implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $content, $filepath;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->content = $data['content'];
        $this->filepath = $data['filepath'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /**
         * We don't do any resolution for covers for now.
         */
        $file = new \Symfony\Component\HttpFoundation\File\File($this->filepath);
        $data = [
            'content' => $this->content,
            'raw' => [
                'filepath' => $this->filepath,
                'purpose' => 'cover',
                'asset_type' => 'image',
                'mime_type' => $file->getMimeType(),
            ],
        ];
        UploadCoverJob::dispatch($data);
    }

    public function failed(\Throwable $exception)
    {
        unlink($this->filepath);
        Log::error($exception);
    }
}
