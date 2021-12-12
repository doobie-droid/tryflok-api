<?php

namespace App\Jobs\Assets\GenerateResolutions;

use App\Jobs\Assets\UploadResource\Video as UploadVideoJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Streaming\FFMpeg;

class Video implements ShouldQueue
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
    public $timeout = 7200;
    public $idleTimeout = 7200;
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
        set_time_limit($this->timeout);
        $ffmpeg =  FFMpeg::create();
        $resource = $ffmpeg->open($this->filepath);
        $localTempFolder = join_path(storage_path(), '/app/hls', $this->asset->id, 'video');
        $hlsKeyName = $this->filename . '.key';
        $baseUrl = join_path(config('services.cloudfront.private_url'), $this->folder);
        //$fullFilename = join_path('contents', $this->content->public_id, 'book', $filename);

        $hlsenc = $resource->hls()
        ->encryption(join_path($localTempFolder, $hlsKeyName), join_path($baseUrl, $hlsKeyName))
        ->setHlsTime(30)
        ->setHlsBaseUrl($baseUrl)
        ->x264()
        ->autoGenerateRepresentations([480, 720, 1080, ]);
        $hlsenc->save(join_path($localTempFolder, $this->filename . '.m3u8'));
        // populate data
        unlink($this->filepath);
        $data = [
            'asset' => $this->asset,
            'folder' => $this->folder,
            'filename' => $this->filename,
            'ext' => $this->ext,
            'full_file_name' => $this->full_file_name,
            'hls_key_filepath' => '',
            'filepath' => '',
            'ts_files' => [],
            'resolutions' => [],
        ];
        //traverse folder and get necessary files
        $generatedFiles = scandir($localTempFolder);
        if ($generatedFiles !== false && is_array($generatedFiles)) {
            $generatedFiles = array_values(array_diff($generatedFiles, ['..', '.', '__MACOSX', '.DS_Store']));
            //handle m3u8 resolutions and key
            foreach ($generatedFiles as $generatedFilename) {
                //determine if it is .m3u8, .ts or .key
                // FILENAME_RESOLUTION.m3u8 or FILENAME.m3u8 if it is the main file
                // FILENAME_RESOLUTION_PARTITION.ts or FILENAME_PARTITION.ts if it is the main file
                $fileMeta = $this->getFileMeta($generatedFilename);
                if ($fileMeta['ext'] === 'key') {
                    $data['hls_key_filepath'] = join_path($localTempFolder, $hlsKeyName);
                } elseif ($fileMeta['ext'] === 'm3u8') {
                    if ($fileMeta['resolution'] === 'main') {
                        $data['filepath'] = join_path($localTempFolder, $generatedFilename);
                    } else {
                        if (! array_key_exists($fileMeta['resolution'], $data['resolutions'])) {
                            $data['resolutions'][$fileMeta['resolution']] = [
                                'resolution' => $fileMeta['resolution'],
                                'filepath' => join_path($localTempFolder, $generatedFilename),
                                'ts_files' => [],
                            ];
                        } else {
                            $data['resolutions'][$fileMeta['resolution']]['resolution'] = $fileMeta['resolution'];
                            $data['resolutions'][$fileMeta['resolution']]['filepath'] = join_path($localTempFolder, $generatedFilename);
                        }
                    }
                } elseif ($fileMeta['ext'] === 'ts') {
                    if ($fileMeta['resolution'] === 'main') {
                        $data['ts_files'][] = join_path($localTempFolder, $generatedFilename);
                    } else {
                        if (! array_key_exists($fileMeta['resolution'], $data['resolutions'])) {
                            $data['resolutions'][$fileMeta['resolution']] = [
                                'resolution' => '',
                                'filepath' => '',
                                'ts_files' => [],
                            ];
                        }
                        $data['resolutions'][$fileMeta['resolution']]['ts_files'][] = join_path($localTempFolder, $generatedFilename);
                    }
                }
            }
        }
        UploadVideoJob::dispatch($data);
    }

    private function getFileMeta($filename)
    {
        $filenameParts = explode('.', $filename);
        $ext = '';
        if (is_array($filenameParts) && count($filenameParts) > 0) {
            $ext = array_pop($filenameParts);
        }
        $resolution = 'main'; // or 780p, 450p etc

        $nameComponents = explode('_', $filenameParts[0]);
        if ($ext === 'm3u8') {
            if (count($nameComponents) === 2) {
                $resolution = $nameComponents[1];
            }
        } elseif ($ext === 'ts') {
            if (count($nameComponents) === 3) {
                $resolution = $nameComponents[1];
            }
        }
        return [
            'ext' => $ext,
            'resolution' => $resolution,
        ];
    }

    public function failed(\Throwable $exception)
    {
        unlink($this->filepath);
        Log::error($exception);
    }
}
