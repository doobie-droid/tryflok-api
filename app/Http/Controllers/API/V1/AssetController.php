<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Constants\Permissions;
use App\Constants\Roles;
use App\Constants\Constants;
use App\Models\Asset;
use App\Jobs\Assets\GenerateResolutions\Image as GenerateImageResolutionsJob;
use App\Jobs\Assets\GenerateResolutions\Video as GenerateVideoResolutionsJob;
use App\Jobs\Assets\GenerateResolutions\Audio as GenerateAudioResolutionsJob;
use App\Jobs\Assets\GenerateResolutions\Pdf as GeneratePdfResolutionsJob;

class AssetController extends Controller
{
    public function uploadFile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files.*' => ['required', 'file'],
                'type' => ['required', 'string', 'regex:(image|pdf|audio|video)'],
            ]);
    
            if ($validator->fails()) {
                return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
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
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function uploadImage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files.*' => ['required', 'image', 'max:1024'],
            ]);
    
            if ($validator->fails()) {
                return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
            $assets = [];
            foreach ($request->file('files') as $file) {
                //create the asset on the table
                $filename = date('Ymd') . Str::random(16);
                $originalName = $file->getClientOriginalName();
                $ext = $file->getClientOriginalExtension();
                $folder = join_path('assets', Str::random(16) . date('Ymd'), 'image');
                $fullFilename = join_path($folder, $filename . "." . $ext);
                $url = join_path(env('PUBLIC_AWS_CLOUDFRONT_URL'), $fullFilename);

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
                $uploadedFilePath = storage_path() . "/app/" . $path;
                GenerateImageResolutionsJob::dispatch([
                    'asset' => $asset,
                    'filepath' => $uploadedFilePath,
                    'folder' => $folder,
                    'ext' => $ext,
                    'filename' => $filename,
                    'full_file_name' => $fullFilename,
                ]);
            }

            return $this->respondWithSuccess("Assets have been created successfully.", [
                'assets' => $assets,
            ]);
        }  catch(\Exception $exception) {
            Log::error($exception);
            foreach ($assets as $asset) {
                $asset->delete();
            }
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function uploadVideo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files.*' => ['required', 'max:4096000'], // 4GB
            ]);
    
            if ($validator->fails()) {
                return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
        }  catch(\Exception $exception) {
            Log::error($exception);
            foreach ($assets as $asset) {
                $asset->delete();
            }
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function uploadAudio(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files.*' => ['required', 'max:204800'], // 200MB
            ]);
    
            if ($validator->fails()) {
                return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $assets = [];
            foreach ($request->file('files') as $file) {
                //create the asset on the table
                $filename = date('Ymd') . Str::random(16);
                $originalName = $file->getClientOriginalName();
                $ext = $file->getClientOriginalExtension();
                $folder = join_path('assets', Str::random(16) . date('Ymd'), 'image');
                $fullFilename = join_path($folder, $filename . "." . $ext);
                $url = join_path(env('PUBLIC_AWS_CLOUDFRONT_URL'), $fullFilename);

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
                $uploadedFilePath = storage_path() . "/app/" . $path;
                GenerateAudioResolutionsJob::dispatch([
                    'asset' => $asset,
                    'filepath' => $uploadedFilePath,
                    'full_file_name' => $fullFilename,
                ]);
            }

            return $this->respondWithSuccess("Assets have been created successfully.", [
                'assets' => $assets,
            ]);
        }  catch(\Exception $exception) {
            Log::error($exception);
            foreach ($assets as $asset) {
                $asset->delete();
            }
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function uploadPdf(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files.*' => ['required', 'max:102400', 'mimetypes:application/pdf'], // 100MB
            ]);
    
            if ($validator->fails()) {
                return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
        }  catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }
}
