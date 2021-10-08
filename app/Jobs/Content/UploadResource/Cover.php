<?php

namespace App\Jobs\Content\UploadResource;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Cover implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $content, $raw;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->content = $data['content'];
        $this->raw = $data['raw'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $nameParts = explode('/', $this->raw['filepath']);
        $filename = end($nameParts);
        $fullFilename = join_path('contents', $this->content->public_id, 'cover', $filename);
        Storage::disk('public_s3')->put($fullFilename, file_get_contents($this->raw['filepath']));
        $this->content->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'public-s3',
            'storage_provider_id' => $fullFilename,
            'url' => join_path(env('PUBLIC_AWS_CLOUDFRONT_URL'), $fullFilename),
            'purpose' => 'cover',
            'asset_type' => 'image',
            'mime_type' => $this->raw['mime_type'],
        ]);
        unlink($this->raw['filepath']);
    }

    public function failed(\Throwable $exception)
    {
        unlink($this->raw['filepath']);
        Log::error($exception);
    }
}
