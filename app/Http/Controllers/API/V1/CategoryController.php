<?php

namespace App\Http\Controllers\API\V1;

use App\Constants\Constants;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function list(Request $request)
    {
        try {
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }
            $search = urldecode($request->query('search', ''));

            $categories = Models\Category::where('name', 'LIKE', "%{$search}%")->orderBy('name')->paginate($limit, ['*'], 'page', $page);

            return $this->respondWithSuccess('Categories retrieved successfully', [
                'categories' => CategoryResource::collection($categories),
                'current_page' => (int) $categories->currentPage(),
                'items_per_page' => (int) $categories->perPage(),
                'total' => (int) $categories->total(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
