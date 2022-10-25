<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Jobs\Assets\GenerateResolutions\Audio as GenerateAudioResolutionsJob;
use App\Jobs\Assets\GenerateResolutions\Image as GenerateImageResolutionsJob;
use App\Jobs\Assets\GenerateResolutions\Pdf as GeneratePdfResolutionsJob;
use App\Jobs\Assets\GenerateResolutions\Video as GenerateVideoResolutionsJob;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Rules\YoutubeUrl;

class AssetController extends Controller
{
    public function uploadFile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files.*' => ['required_without:youtube_url', 'file'],
                'type' => ['required', 'string', 'in:image,pdf,audio,video'],
                'youtube_url' => ['required_without:files', 'string', new YouTubeUrl],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            switch ($request->type) {
                case 'image':
                    $response = $this->uploadImage($request);
                    break;
                case 'pdf':
                    $response = $this->uploadPdf($request);
                    break;
                case 'audio':
                    $response = $this->uploadAudio($request);
                    break;
                case 'video':
                    $response = $this->uploadVideo($request);
                    break;
            }
            return $response;
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function uploadImage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files.*' => ['required', 'image', 'max:5120', 'mimetypes:image/gif,image/jpeg,image/png'], //5MB
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
            $assets = [];
            foreach ($request->file('files') as $file) {
                //create the asset on the table
                $filename = date('Ymd') . Str::random(16);
                $originalName = $file->getClientOriginalName();
                $ext = $file->getClientOriginalExtension();
                $folder = join_path('assets', Str::random(16) . date('Ymd'), 'image');
                $fullFilename = join_path($folder, $filename . '.' . $ext);
                $url = join_path(config('flok.public_media_url'), $fullFilename);

                $asset = Asset::create([
                    'url' => $url,
                    'storage_provider' => 'public-s3',
                    'storage_provider_id' => $fullFilename,
                    'asset_type' => 'image',
                    'mime_type' => $file->getMimeType(),
                ]);
                //append to assets array
                $asset->original_name = $originalName;
                $assets[] = $asset;
                //delegate upload to job
                $path = Storage::disk('local')->put('uploads/images', $file);
                $uploadedFilePath = storage_path() . '/app/' . $path;
                GenerateImageResolutionsJob::dispatch([
                    'asset' => $asset,
                    'filepath' => $uploadedFilePath,
                    'folder' => $folder,
                    'ext' => $ext,
                    'filename' => $filename,
                    'full_file_name' => $fullFilename,
                ]);
            }

            return $this->respondWithSuccess('Assets have been created successfully.', [
                'assets' => $assets,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            foreach ($assets as $asset) {
                $asset->delete();
            }
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function uploadVideo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files.*' => ['required', 'max:4096000', 'mimetypes:video/mp4,video/mpeg,video/ogg,video/x-msvideo,video/webm'], // 4GB
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }         
            $assets = [];

            if ( ! is_null($request->file('files') ))  {
                foreach ($request->file('files') as $file) {
                    //create the asset on the table
                    $filename = date('Ymd') . Str::random(16);
                    $originalName = $file->getClientOriginalName();
                    $ext = $file->getClientOriginalExtension();
                    $folder = join_path('assets', Str::random(16) . date('Ymd'), 'video');
                    $fullFilename = join_path($folder, $filename . '.m3u8');
                    $url = join_path(config('flok.private_media_url'), $fullFilename);
    
                    $asset = Asset::create([
                        'url' => $url,
                        'storage_provider' => 'private-s3',
                        'storage_provider_id' => $fullFilename,
                        'asset_type' => 'video',
                        'mime_type' => 'application/vnd.apple.mpegurl',
                    ]);
                     //append to assets array
                    $asset->original_name = $originalName;
                    $assets[] = $asset;
                    //delegate upload to job
                    $path = Storage::disk('local')->put('uploads/videos', $file);
                    $uploadedFilePath = storage_path() . '/app/' . $path;
                    GenerateVideoResolutionsJob::dispatch([
                        'asset' => $asset,
                        'filepath' => $uploadedFilePath,
                        'folder' => $folder,
                        'ext' => $ext,
                        'filename' => $filename,
                        'full_file_name' => $fullFilename,
                    ]);
            }
            }

            if ( ! is_null($request->youtube_url)) {
                preg_match("/(\/|%3D|v=|vi=)([0-9A-z-_]{11})([%#?&]|$)/", $request->youtube_url, $match);
                if (! empty($match))
                {
                    $videoId = $match[2];
                }
                if (empty($match))
                {
                    parse_str( parse_url( $request->youtube_url, PHP_URL_QUERY ), $array );        
                    $index = array_key_first($array);                    
                    $value = $array[$index];        
                    if (($value) != '')
                    {
                        $videoId = $value;
                    }
                    else{
                        $videoId = $index;
                    }
                }
                $asset = Asset::create([
                    'url' => 'https://youtube.com/embed/'.$videoId,
                    'storage_provider' => 'youtube',
                    'storage_provider_id' => $videoId,
                    'asset_type' => 'video',
                    'mime_type' => 'video/mp4',
                ]);
                    //append to assets array
                    $assets[] = $asset;                
            }
            return $this->respondWithSuccess('Assets have been created successfully.', [
                'assets' => $assets,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            foreach ($assets as $asset) {
                $asset->delete();
            }
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function uploadAudio(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files.*' => ['required', 'max:1048576', 'mimetypes:audio/ogg,audio/mpeg,audio/aac,audio/wav,audio/webm'], // 200MB
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $assets = [];
            foreach ($request->file('files') as $file) {
                //create the asset on the table
                $filename = date('Ymd') . Str::random(16);
                $originalName = $file->getClientOriginalName();
                $ext = $file->getClientOriginalExtension();
                $folder = join_path('assets', Str::random(16) . date('Ymd'), 'audio');
                $fullFilename = join_path($folder, $filename . '.' . $ext);
                $url = join_path(config('flok.private_media_url'), $fullFilename);

                $asset = Asset::create([
                    'url' => $url,
                    'storage_provider' => 'private-s3',
                    'storage_provider_id' => $fullFilename,
                    'asset_type' => 'audio',
                    'mime_type' => $file->getMimeType(),
                ]);
                //append to assets array
                $asset->original_name = $originalName;
                $assets[] = $asset;
                //delegate upload to job
                $path = Storage::disk('local')->put('uploads/audio', $file);
                $uploadedFilePath = storage_path() . '/app/' . $path;
                GenerateAudioResolutionsJob::dispatch([
                    'asset' => $asset,
                    'filepath' => $uploadedFilePath,
                    'full_file_name' => $fullFilename,
                ]);
            }

            return $this->respondWithSuccess('Assets have been created successfully.', [
                'assets' => $assets,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            foreach ($assets as $asset) {
                $asset->delete();
            }
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function uploadPdf(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files.*' => ['required', 'max:102400', 'mimetypes:application/pdf'], // 100MB
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
            $assets = [];

            foreach ($request->file('files') as $file) {
                //create the asset on the table
                $filename = date('Ymd') . Str::random(16);
                $originalName = $file->getClientOriginalName();
                $ext = $file->getClientOriginalExtension();
                $folder = join_path('assets', Str::random(16) . date('Ymd'), 'pdf');
                $fullFilename = join_path($folder, $filename . '.' . $ext);
                $url = join_path(config('flok.private_media_url'), $fullFilename);

                $asset = Asset::create([
                    'url' => $url,
                    'storage_provider' => 'private-s3',
                    'storage_provider_id' => $fullFilename,
                    'asset_type' => 'pdf',
                    'mime_type' => $file->getMimeType(),
                ]);
                //append to assets array
                $asset->original_name = $originalName;
                $assets[] = $asset;
                //delegate upload to job
                $path = Storage::disk('local')->put('uploads/pdf', $file);
                $uploadedFilePath = storage_path() . '/app/' . $path;
                GeneratePdfResolutionsJob::dispatch([
                    'asset' => $asset,
                    'filepath' => $uploadedFilePath,
                    'filename' => $filename,
                    'full_file_name' => $fullFilename,
                ]);
            }

            return $this->respondWithSuccess('Assets have been created successfully.', [
                'assets' => $assets,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            foreach ($assets as $asset) {
                $asset->delete();
            }
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function importAssetFromThirdParty(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'items.*' => ['required'],
                'items.*.provider' => ['required', 'string'],
                'items.*.assets.url' => ['required', 'string'],
                'items.*.assets.type' => ['required', 'string', 'in:video,live-video'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            switch ($request->items['provider']) {
                case 'youtube':
                    $response = $this->importFromAgora($request);
                    break;
                case 'agora':
                    $response = $this->importFromYoutube($request);
                    break;
            }
            return $response;

        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
}
