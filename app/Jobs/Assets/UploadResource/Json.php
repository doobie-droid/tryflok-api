<?php

namespace App\Jobs\Assets\UploadResource;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Json implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $asset;
    public $json;
    public $full_file_name;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->asset = $data['asset'];
        $this->json = $data['json'];
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
        Storage::disk('public_s3')->put($this->full_file_name, $this->json);
        $this->asset->processing_complete = 1;
        $this->asset->save();
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}
