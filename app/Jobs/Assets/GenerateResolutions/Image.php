<?php

namespace App\Jobs\Assets\GenerateResolutions;

use App\Jobs\Assets\UploadResource\Image as UploadImageJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class Image implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    public $asset;
    public $filepath;
    public $folder;
    public $filename;
    public $ext;
    public $full_file_name;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->asset = $data['asset'];
        $this->filepath = $data['filepath'];
        $this->folder = $data['folder'];
        $this->filename = $data['filename'];
        $this->ext = $data['ext'];
        $this->full_file_name = $data['full_file_name'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /**
         * We don't do any resolution for images for now.
         */
        UploadImageJob::dispatch([
            'asset' => $this->asset,
            'filepath' => $this->filepath,
            'folder' => $this->folder,
            'filename' => $this->filename,
            'ext' => $this->ext,
            'full_file_name' => $this->full_file_name,
        ]);
    }

    public function failed(\Throwable $exception)
    {
        unlink($this->filepath);
        Log::error($exception);
    }
}
