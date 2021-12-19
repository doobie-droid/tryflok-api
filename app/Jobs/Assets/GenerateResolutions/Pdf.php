<?php

namespace App\Jobs\Assets\GenerateResolutions;

use App\Jobs\Assets\EncryptResource\Pdf as EncryptPdfJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class Pdf implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $asset;
    public $filepath;
    public $filename;
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
        $this->filename = $data['filename'];
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
         * We don't do any resolution for pdf for now.
         */
        EncryptPdfJob::dispatch([
            'asset' => $this->asset,
            'filepath' => $this->filepath,
            'filename' => $this->filename,
            'full_file_name' => $this->full_file_name,
        ]);
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
        unlink($this->filepath);
    }
}
