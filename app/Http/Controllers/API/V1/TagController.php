<?php

namespace App\Http\Controllers\API\V1;

use App\Constants\Constants;
use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tags' => ['required'],
                'tags.*.name' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
            foreach ($request->tags as $tag) {
                $dbTag = Models\Tag::where('name', $tag)->first();
                if ( is_null($dbTag)) {
                    Models\Tag::create([
                        'name' => strToLower($tag['name']),
                        'tag_priority' => 1,
                        'user_id' => $request->user()->id,
                    ]);
                }
            }
            return $this->respondWithSuccess('Tags created successfully');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function delete(Request $request, $id)
    {
        $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
            'id' => ['required', 'string', 'exists:tags,id'],
        ]);

        if ($validator->fails()) {
            return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
        }
        if ($request->user() == null || $request->user()->id == null) {
            $user_id = '';
        } else {
            $user_id = $request->user()->id;
        }
        //make sure user owns tag
        $tag = Models\Tag::where('id', $id)->where('user_id', $user_id)->first();
        if ( is_null($tag))
        {
            return $this->respondBadRequest('You cannot delete this tag because you do not own it');  
        }
        if ( ! is_null($tag))
        {
            $tag->delete();
        }
        return $this->respondWithSuccess('Tags Deleted successfully', [
            'tag' => $tag,
        ]);
    }
}
