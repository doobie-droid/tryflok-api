<?php

namespace App\Jobs\Assets\GenerateResolutions;

use App\Jobs\Assets\UploadResource\Image as UploadImageJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image as ImageManipulator;

class Image implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $asset;
    public $filepath;
    public $folder;
    public $filename;
    public $ext;
    public $full_file_name;
    public $compressed_filepath;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->asset = $data['asset'];
        $this->filepath = $data['filepath'];
        $this->folder = $data['folder'];
        $this->filename = $data['filename'];
        $this->ext = $data['ext'];
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
        // compress image
        $compressed_folder_path = join_path(
            storage_path(),
            '/app/uploads/images',
            $this->asset->id
        );
            $compressed_file_name = 'compressesed-' . date('Ymdhis') . '.' .  $this->ext;
        $destination = join_path($compressed_folder_path, $compressed_file_name);
        $this->compressed_filepath = $destination;
        mkdir($compressed_folder_path, 0777, true);
        $this->compressImage($this->filepath, $destination);
        UploadImageJob::dispatch([
            'asset' => $this->asset,
            'filepath' => $destination,
            'folder' => $this->folder,
            'filename' => $this->filename,
            'ext' => $this->ext,
            'full_file_name' => $this->full_file_name,
        ]);
        unlink($this->filepath);
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
        unlink($this->filepath);
        unlink($this->compressed_filepath);
    }

    private function compressImage($source, $destination)
    {
        $image = ImageManipulator::make($source);
        $image->orientate();
        $image->resize(350, 350, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $compression_level = 80;
        $file_size = $image->filesize() * 1024 * 1024; // multiply by 1024 * 1024 to convert to MB

        if ($file_size <= .1) {
            $compression_level = 90;
        } elseif ($file_size <= .2) {
            $compression_level = 90;
        } elseif ($file_size < .5) {
            $compression_level = 90;
        } elseif ($file_size <= 1) {
            $compression_level = 50;
        } elseif ($file_size <= 2) {
            $compression_level = 25;
        } elseif ($file_size <= 3) {
            $compression_level = 20;
        } elseif ($file_size <= 4) {
            $compression_level = 20;
        } else {
            $compression_level = 20;
        }
        $image->encode('jpg', $compression_level);
        $image->save($destination);
    }
}
