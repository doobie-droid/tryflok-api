<?php

namespace App\Jobs\Assets\GenerateResolutions;

use App\Jobs\Assets\UploadResource\Image as UploadImageJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $compression_level = 80;
        $originalFileSize = filesize($this->filepath) * 1024 * 1024; // multiply by 1024 * 1024 to convert to MB

        if ($originalFileSize <= .1) {
            $compression_level = 90;
        } else if ($originalFileSize <= .2) {
            $compression_level = 80;
        } else if ($originalFileSize < .5) {
            $compression_level = 70;
        } else if ($originalFileSize <= 1) {
            $compression_level = 60;
        } else if ($originalFileSize <= 2) {
            $compression_level = 50;
        } else if ($originalFileSize <= 3) {
            $compression_level = 40;
        } else if ($originalFileSize <= 4) {
            $compression_level = 30;
        } else {
            $compression_level = 20;
        }
        // compress image
        $compressed_folder_path = join_path(
            storage_path(),
            '/app/uploads/images',
            $this->asset->id);
            $compressed_file_name = 'compressesed-' . date('Ymdhis') . '.' .  $this->ext;
        $destination = join_path($compressed_folder_path, $compressed_file_name);
        $this->compressed_filepath = $destination;
        mkdir($compressed_folder_path, 0777, true);
        $this->compressImage($this->filepath, $destination, $compression_level);
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

    private function compressImage($source, $destination, $quality)
    {
        $imageInfo = getimagesize($source);
        $mimetype = $imageInfo['mime'];
        $image = $this->createImageFromMimeType($source, $mimetype);
        $this->generateCompressedImageBasedOnMimeType($image, $destination, $imageInfo, $quality);
    }

    private function createImageFromMimeType($source, $mimetype)
    {
        switch ($mimetype) {
            case 'image/jpeg':
                return imagecreatefromjpeg($source);
                break;
            case 'image/gif':
                return imagecreatefromgif($source);
                break;
            case 'image/png':
                return imagecreatefrompng($source);
                break;
            default:
                return imagecreatefromjpeg($source);
        }
    }

    private function generateCompressedImageBasedOnMimeType($image, $destination, $imageInfo, $quality)
    {
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                imagejpeg($image, $destination, $quality);
                break;
            case 'image/gif':
                imagegif($image, $destination);
                break;
            case 'image/png':
                $pngQuality = 9 - bcdiv($quality, 100, 0);
                list($width, $height) = $imageInfo;
                $targetImage = imagecreatetruecolor($width, $height);   
                imagealphablending($targetImage, false); 
                imagesavealpha($targetImage, true);
                $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
                imagefilledrectangle($targetImage, 0, 0, $width, $height, $transparent);
                imagecopyresampled($targetImage, $image, 
                    0, 0, 
                    0, 0, 
                    $width, $height, 
                    $width, $height );
                imagepng($targetImage, $destination, $pngQuality);
                break;
            default:
                imagejpeg($image, $destination, $quality);
        }
    }
}
