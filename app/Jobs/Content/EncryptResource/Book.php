<?php

namespace App\Jobs\Content\EncryptResource;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\Content\UploadResource\Book as UploadBookJob;
use App\Utils\Crypter;
use Illuminate\Support\Str;

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
        $key = sodium_crypto_secretbox_keygen();
        $keyBase64 = base64_encode($key);
        $fileContents = file_get_contents($this->raw['filepath']);
        $fileContentsBase64 = base64_encode($fileContents);
        $encryptedFileBase64 = Crypter::symmetricalEncryptUsingOtherKey($fileContentsBase64, $keyBase64);
        $folder = join_path(storage_path(), '/app/encrypted-files', $this->content->public_id, 'book');
        $filename = date('Ymd') . Str::random(16);
        $tempPathForEncryptedFile = join_path($folder, $filename);
        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }
        file_put_contents($tempPathForEncryptedFile, $encryptedFileBase64);
        // encrypt the encryption key too
        $encryptedEncryptionKey = Crypter::symmetricalEncryptUsingOwnKey($keyBase64);
        unlink($this->raw['filepath']);
        $this->raw['filepath'] = $tempPathForEncryptedFile;
        $this->raw['encryption_key'] = $encryptedEncryptionKey;
        UploadBookJob::dispatch([
            'content' => $this->content,
            'raw' => $this->raw,
        ]);
    }

    public function failed(\Throwable $exception)
    {
        unlink($this->raw['filepath']);
        Log::error($exception);
    }
}
