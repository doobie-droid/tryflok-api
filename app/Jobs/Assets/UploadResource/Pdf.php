<?php

namespace App\Jobs\Assets\UploadResource;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Pdf implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    public $asset;
    public $filepath;
    public $full_file_name;
    public $encryption_key;
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
        $this->encryption_key = $data['encryption_key'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Storage::disk('private_s3')->put($this->full_file_name, file_get_contents($this->filepath));
        $this->asset->encryption_key = $this->encryption_key;
        $this->asset->save();
        unlink($this->filepath);
    }

    public function failed(\Throwable $exception)
    {
        unlink($this->filepath);
        Log::error($exception);
    }
}
