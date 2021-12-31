<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LanguageController extends Controller
{
    public function list(Request $request)
    {
        try {
            return $this->respondWithSuccess('Languages retrieved successfully', [
                'languages' => Cache::rememberForever('request:languages', function () {
                    return Models\Language::orderBy('name')->get();
                }),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
