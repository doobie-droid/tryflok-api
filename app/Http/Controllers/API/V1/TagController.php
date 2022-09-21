<?php

namespace App\Http\Controllers\API\V1;

use App\Constants\Constants;
use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TagController extends Controller
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

            $tags = Models\Tag::where('name', 'LIKE', "%{$search}%")->where('tag_priority', 1)->orderBy('name')->paginate($limit, ['*'], 'page', $page);

            return $this->respondWithSuccess('Tags retrieved successfully', [
                'tags' => TagResource::collection($tags),
                'current_page' => (int) $tags->currentPage(),
                'items_per_page' => (int) $tags->perPage(),
                'total' => (int) $tags->total(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
