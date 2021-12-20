<?php

namespace App\Jobs\Assets\UploadResource;

use App\Mail\User\ContentReadyMail;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class Video implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $asset;
    public $filepath;
    public $ts_files;
    public $folder;
    public $filename;
    public $ext;
    public $full_file_name;
    public $resolutions;
    public $hls_key_filepath;
    public $timeout = 3600;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->asset = $data['asset'];
        $this->folder = $data['folder'];
        $this->filepath = $data['filepath'];
        $this->ts_files = $data['ts_files'];
        $this->filename = $data['filename'];
        $this->ext = $data['ext'];
        $this->full_file_name = $data['full_file_name'];
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
        $baseUrl = join_path(config('services.cloudfront.private_url'), $this->folder);
        //upload encryption key
        $nameParts = explode('/', $this->hls_key_filepath);
        $filename = end($nameParts);
        $fullFilename = join_path($this->folder, $filename);
        Storage::disk('private_s3')->put($fullFilename, file_get_contents($this->hls_key_filepath));
        unlink($this->hls_key_filepath);
        //upload raw file
        Storage::disk('private_s3')->put($this->full_file_name, file_get_contents($this->filepath));
        unlink($this->filepath);
        //upload ts files
        foreach ($this->ts_files as $tsPath) {
            $nameParts = explode('/', $tsPath);
            $filename = end($nameParts);
            $fullFilename = join_path($this->folder, $filename);
            Storage::disk('private_s3')->put($fullFilename, file_get_contents($tsPath));
            unlink($tsPath);
        }
        //upload resolutions
        foreach ($this->resolutions as $name => $data) {
            $nameParts = explode('/', $data['filepath']);
            $filename = end($nameParts);
            $fullFilename = join_path($this->folder, $filename);
            Storage::disk('private_s3')->put($fullFilename, file_get_contents($data['filepath']));
            unlink($data['filepath']);
            $this->asset->resolutions()->create([
                'storage_provider' => 'private-s3',
                'storage_provider_id' => $fullFilename,
                'url' => join_path($baseUrl, $filename),
                'resolution' => $data['resolution'],
            ]);

            foreach ($data['ts_files'] as $tsPath) {
                $nameParts = explode('/', $tsPath);
                $filename = end($nameParts);
                $fullFilename = join_path($this->folder, $filename);
                Storage::disk('private_s3')->put($fullFilename, file_get_contents($tsPath));
                unlink($tsPath);
            }
        }

        $this->asset->processing_complete = 1;
        $this->asset->save();
        $content = $this->asset->contents()->first();
        if (! is_null($content)) {
            $message = "Your video content titles \"{$content->title}\" is ready for viewing.";

            $client = new Client;
            $url = 'https://fcm.googleapis.com/fcm/send';
            $authorization_key = config('services.google.fcm_server_key');
            foreach ($content->user->notificationTokens as $notification_token) {
                $client->post($url, [
                    'headers' => [
                        'Authorization' => "key={$authorization_key}",
                    ],
                    'json' => [
                        'to' => $notification_token->token,
                        'notification' => [
                            'title' => 'You just got tipped!',
                            'body' => $message,
                        ],
                        'data' => [
                            'message' => $message,
                            'notificable_type' => 'content',
                            'notificable_id' => $content->id,
                        ],
                    ],
                ]);
            }

            Mail::to($content->user)->send(new ContentReadyMail([
                'user' => $content->user,
                'message' => $message,
            ]));
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
        unlink($this->filepath);
        unlink($this->hls_key_filepath);
        foreach ($this->ts_files as $filepath) {
            unlink($filepath);
        }
        foreach ($this->resolutions as $name => $data) {
            unlink($data['filepath']);
            foreach ($data['ts_files'] as $filepath) {
                unlink($filepath);
            }
        }
    }
}
