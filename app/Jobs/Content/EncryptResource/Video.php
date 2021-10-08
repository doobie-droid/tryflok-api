<?php

namespace App\Jobs\Content\EncryptResource;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Content\UploadResource\Video as UploadVideoJob;
use App\Utils\Crypter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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
        $key = sodium_crypto_secretbox_keygen();
        $keyBase64 = base64_encode($key);
        $encryptedEncryptionKey = Crypter::symmetricalEncryptUsingOwnKey($keyBase64);
        //handle raw
        $encryptedMain = $this->encryptFile($this->raw['filepath'], $keyBase64);
        unlink($this->raw['filepath']);
        $this->raw['filepath'] = $encryptedMain;
        $this->raw['encryption_key'] = $encryptedEncryptionKey;
        //handle resolutions
        foreach ($this->resolutions as $name => $data) {
            $encrypted = $this->encryptFile($data['filepath'], $keyBase64);
            unlink($data['filepath']);
            $this->resolutions[$name]['filepath'] = $encrypted;
            $this->resolutions[$name]['encryption_key'] = $encryptedEncryptionKey;
        }

        UploadVideoJob::dispatch([
            'content' => $this->content,
            'raw' => $this->raw,
            'resolutions' => $this->resolutions,
            'hls_key_filepath' => $this->hls_key_filepath,
        ]);
    }

    /**
     * Encrypt the file contained in the path
     * 
     * @param String $filepath
     * @param String $key - base 64 encoding of encryption key
     * 
     * @return String path to encrypted file
     */
    private function encryptFile($filepath, $key)
    {
        $fileContents = file_get_contents($filepath);
        $fileContentsBase64 = base64_encode($fileContents);
        $encryptedFileBase64 = Crypter::symmetricalEncryptUsingOtherKey($fileContentsBase64, $key);
        $folder = join_path(storage_path(), '/app/encrypted-files', $this->content->public_id, 'video');
        $filename = date('Ymd') . Str::random(16);
        $pathToEncryptedFile = join_path($folder, $filename);
        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }
        file_put_contents($pathToEncryptedFile, $encryptedFileBase64);
        return $pathToEncryptedFile;
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
