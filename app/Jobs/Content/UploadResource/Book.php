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

class Book implements ShouldQueue
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
        $fullFilename = join_path('contents', $this->content->public_id, 'book', $filename);
        Storage::disk('private_s3')->put($fullFilename, file_get_contents($this->raw['filepath']));
        $this->content->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'private-s3',
            'storage_provider_id' => $fullFilename,
            'url' => join_path(env('PRIVATE_AWS_CLOUDFRONT_URL'), $fullFilename),
            'purpose' => $this->raw['purpose'],
            'asset_type' => $this->raw['asset_type'],
            'mime_type' => $this->raw['mime_type'],
            'page' => $this->raw['page'],
            'encryption_key' => $this->raw['encryption_key'],
        ]);
        unlink($this->raw['filepath']);
    }

    public function failed(\Throwable $exception)
    {
        unlink($this->raw['filepath']);
        Log::error($exception);
    }
}
