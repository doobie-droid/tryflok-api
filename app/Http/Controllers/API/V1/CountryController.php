<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CountryController extends Controller
{
    public function list(Request $request)
    {
        try {
            return $this->respondWithSuccess('Countries retrieved successfully', [
                'countries' => Cache::rememberForever('request:countries', function () {
                    return Models\Country::orderBy('name')->get();
                }),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
