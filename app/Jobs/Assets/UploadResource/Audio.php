<?php

namespace App\Jobs\Assets\UploadResource;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Audio implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $asset;
    public $filepath;
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
        $this->full_file_name = $data['full_file_name'];
        $this->onConnection('redis_local');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Storage::disk('private_s3')->put($this->full_file_name, file_get_contents($this->filepath));
        $this->asset->processing_complete = 1;
        $this->asset->save();
        $content = $this->asset->contents()->first();
        if (! is_null($content)) {
            $content->is_available = 1;
            $content->save();
        }
        
        unlink($this->filepath);
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
        unlink($this->filepath);
    }
}
