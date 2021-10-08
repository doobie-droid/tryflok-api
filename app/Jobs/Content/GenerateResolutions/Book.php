<?php

namespace App\Jobs\Content\GenerateResolutions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use \PhpZip\ZipFile;
use App\Jobs\Content\EncryptResource\Book as EncryptBookJob;

class Book implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $content, $filepath, $format;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->content = $data['content'];
        $this->filepath = $data['filepath'];
        $this->format = $data['format'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $assetType = "";
        $purpose = "";
        switch ($this->format) {
            case "pdf":
                $assetType = "pdf";
                $purpose = "pdf-book-page";
                break;
            case "2d-image":
                $assetType = "image";
                $purpose = "image-book-page";
                break;
        }

        $zip = new ZipFile();

        try {
            $extractFolder = storage_path() . '/app/zips/' . $this->content->public_id;

            if (!file_exists($extractFolder)) {
                mkdir($extractFolder, 0755, true);
            }

            $zip->openFile($this->filepath)
            ->extractTo($extractFolder);
            unlink($this->filepath); // zip no longer needed as it has been extracted

            $files = scandir($extractFolder);
            if ($files !== false && is_array($files)) {
                $files = array_values(array_diff($files, array('..', '.', '__MACOSX')));
                $subfolder = $files[0];
                $files = scandir($extractFolder . "/" . $files[0]);
                $files = array_values(array_diff($files, array('..', '.', '.DS_Store')));

                foreach ($files as $filename) {
                    /**
                     * We don't do any resolution for books for now.
                     */
                    //get page
                    $nameParts = explode(".", $filename);
                    $pageNumber = abs((int)$nameParts[0]);
                    if ($pageNumber > 2000) {
                        $pageNumber = $pageNumber % 2000;
                    }
                    $filePath = $extractFolder . "/" . $subfolder . "/" . $filename;
                    $file = new \Symfony\Component\HttpFoundation\File\File($filePath);
                    
                    EncryptBookJob::dispatch([
                        'content' => $this->content,
                        'raw' => [
                            'filepath' => $filePath,
                            'purpose' => $purpose,
                            'asset_type' => $assetType,
                            'mime_type' => $file->getMimeType(),
                            'page' => $pageNumber,
                        ],
                    ]);
                }
            }

        }  catch(\PhpZip\Exception\ZipException $e){
            Log::error($e);
            unlink($this->filepath);
            $dirname = storage_path() . '/app/zips/' . $this->content->public_id;
            array_map('unlink', glob("$dirname/*"));
            array_map('rmdir', glob("$dirname/*"));
            rmdir($dirname);
        }
        finally{
            $zip->close();
        }
    }

    public function failed(\Throwable $exception)
    {
        unlink($this->filepath);
        $dirname = storage_path() . '/app/zips/' . $this->content->public_id;
        array_map('unlink', glob("$dirname/*"));
        array_map('rmdir', glob("$dirname/*"));
        rmdir($dirname);
        Log::error($exception);
    }
}
