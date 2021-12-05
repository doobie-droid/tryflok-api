<?php

namespace App\Jobs\Assets\EncryptResource;

use App\Jobs\Assets\UploadResource\Pdf as UploadPdfJob;
use App\Utils\Crypter;
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
        $key = sodium_crypto_secretbox_keygen();
        $keyBase64 = base64_encode($key);
        $fileContents = file_get_contents($this->filepath);
        $fileContentsBase64 = base64_encode($fileContents);
        $encryptedFileBase64 = Crypter::symmetricalEncryptUsingOtherKey($fileContentsBase64, $keyBase64);
        $folder = join_path(storage_path(), '/app/encrypted-files', $this->asset->id, 'pdf');
        $filename = $this->filename;
        $tempPathForEncryptedFile = join_path($folder, $filename);
        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }
        file_put_contents($tempPathForEncryptedFile, $encryptedFileBase64);
        // encrypt the encryption key too
        $encryptedEncryptionKey = Crypter::symmetricalEncryptUsingOwnKey($keyBase64);
        unlink($this->filepath);
        $this->filepath = $tempPathForEncryptedFile;
        UploadPdfJob::dispatch([
            'asset' => $this->asset,
            'filepath' => $this->filepath,
            'full_file_name' => $this->full_file_name,
            'encryption_key' => $encryptedEncryptionKey,
        ]);
    }

    public function failed(\Throwable $exception)
    {
        unlink($this->filepath);
        Log::error($exception);
    }
}
