<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ContinentController extends Controller
{
    public function list(Request $request)
    {
        try {
            return $this->respondWithSuccess('Continents retrieved successfully', [
                'continents' => Cache::rememberForever('request:continents', function () {
                    return Models\Continent::orderBy('name')->get();
                }),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
