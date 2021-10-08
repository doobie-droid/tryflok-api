<?php

namespace App\Jobs\Content\UploadResource;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Video implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $content, $raw, $resolutions, $hls_key_filepath;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->content = $data['content'];
        $this->raw = $data['raw'];
        $this->resolutions = $data['resolutions'];
        $this->hls_key_filepath = $data['hls_key_filepath'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $awsFolder = join_path('contents', $this->content->public_id, 'video');
        $baseUrl = join_path(env('PRIVATE_AWS_CLOUDFRONT_URL'), $awsFolder);
        //upload encryption key
        $nameParts = explode('/', $this->hls_key_filepath);
        $filename = end($nameParts);
        $fullFilename = join_path($awsFolder, $filename);
        Storage::disk('private_s3')->put($fullFilename, file_get_contents($this->hls_key_filepath));
        unlink($this->hls_key_filepath);
        //upload raw file
        $nameParts = explode('/', $this->raw['filepath']);
        $filename = end($nameParts);
        $fullFilename = join_path($awsFolder, $filename);
        Storage::disk('private_s3')->put($fullFilename, file_get_contents($this->raw['filepath']));
        unlink($this->raw['filepath']);
        $asset = $this->content->assets()->create([
            'public_id' => uniqid(rand()),
            'storage_provider' => 'private-s3',
            'storage_provider_id' => $fullFilename,
            'url' => join_path($baseUrl, $filename),
            'purpose' => 'video',
            'asset_type' => 'video',
            'mime_type' => 'application/vnd.apple.mpegurl',
            'encryption_key' => $this->raw['encryption_key'],
        ]);
        //upload resolutions
        foreach ($this->resolutions as $name => $data) {
            $nameParts = explode('/', $data['filepath']);
            $filename = end($nameParts);
            $fullFilename = join_path($awsFolder, $filename);
            Storage::disk('private_s3')->put($fullFilename, file_get_contents( $data['filepath']));
            unlink( $data['filepath']);
            $asset->resolutions()->create([
                'public_id' => uniqid(rand()),
                'storage_provider' => 'private-s3',
                'storage_provider_id' => $fullFilename,
                'url' => join_path($baseUrl, $filename),
                'resolution' => $data['resolution'],
                'encryption_key' => $this->raw['encryption_key'],
            ]);

            foreach ($data['ts_files'] as $tsPath) {
                $nameParts = explode('/', $tsPath);
                $filename = end($nameParts);
                $fullFilename = join_path($awsFolder, $filename);
                Storage::disk('private_s3')->put($fullFilename, file_get_contents($tsPath));
                unlink($tsPath);
            }
        }
    }

    public function failed(\Throwable $exception)
    {
        unlink($this->raw['filepath']);
        unlink($this->hls_key_filepath);
        foreach ($this->raw['ts_files'] as $filepath) {
            unlink($filepath);
        }
        foreach ($this->resolutions as $name => $data) {
            unlink($data['filepath']);
            foreach ($data['ts_files'] as $filepath) {
                unlink($filepath);
            }
        }
        Log::error($exception);
    }
}
