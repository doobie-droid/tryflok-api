<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Constants\Permissions;
use App\Constants\Roles;
use App\Constants\Constants;
use App\Http\Resources\UserResource;
use App\Services\Storage\Storage as Storage;
use App\Models\User;
use App\Models\Content;
use App\Models\Collection;
use App\Models\Category;
use App\Models\Price;
use App\Models\Review;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\Collection\DispatchContentUserablesUpdate as DispatchContentUserablesUpdateJob;
use App\Jobs\Collection\DispatchCollectionUserablesUpdate as DispatchCollectionUserablesUpdateJob;
use App\Rules\AssetType as AssetTypeRule;
use App\Http\Resources\CollectionResource;

class CollectionController extends Controller
{
    public function createDigiverse(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => ['required', 'string'],
                'description' => ['required', 'string'],
                'cover.asset_id' => ['sometimes', 'nullable', 'string', 'exists:assets,id', new AssetTypeRule('image')],
                'price' => ['required',],
                'price.amount' => ['required', 'min:0', 'numeric'],
                'price.interval' => ['required', 'string', 'regex:(one-off|monthly)'],
                'price.interval_amount' => ['required','min:1', 'max:1', 'numeric', 'integer'],
                'tags' => ['sometimes',],
                'tags.*' => ['required', 'string', 'exists:tags,id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $user = $request->user();
            $digiverse = Collection::create([
                'title' => $request->title,
                'description' => $request->description,
                'user_id' => $user->id,
                'type' => 'digiverse',
                'is_available' => 0,
                'approved_by_admin' => 0,
                'show_only_in_collections' => 0,
                'views' => 0,
            ]);

            $digiverse->benefactors()->create([
                'user_id' => $user->id,
                'share' => 100,
            ]);

            $digiverse->prices()->create([
                'amount' => $request->price['amount'],
                'interval' => $request->price['interval'],
                'interval_amount' => $request->price['interval_amount'],
            ]);

            if (isset($request->tags) && is_array($request->tags)) {
                foreach ($request->tags as $tag_id) {
                    $digiverse->tags()->attach($tag_id, [
                        'id' => Str::uuid(),
                    ]);
                }
            }
            

            if (!is_null($request->cover) && array_key_exists('asset_id', $request->cover)) {
                $digiverse->cover()->attach($request->cover['asset_id'], [
                    'id' => Str::uuid(),
                    'purpose' => 'cover'
                ]);
            }

            return $this->respondWithSuccess("Digiverse has been created successfully.", [
                'digiverse' => new CollectionResource($digiverse),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function getDigiverse(Request $request, $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'string', 'exists:collections,id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $digiverse = Collection::where('id', $id)->first();
            return $this->respondWithSuccess("Digiverse retrieved successfully.", [
                'digiverse' => new CollectionResource($digiverse),
            ]);

        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function updateDigiverse(Request $request, $id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:collections,id'],
                'title' => ['sometimes', 'nullable', 'string'],
                'description' => ['sometimes', 'nullable', 'string'],
                'cover.asset_id' => ['sometimes', 'nullable', 'string', 'exists:assets,id', new AssetTypeRule('image')],
                'is_available' => ['sometimes', 'numeric', 'integer', 'regex:(0|1)'],
                'price' => ['sometimes', 'nullable',],
                'price.id' => ['sometimes', 'exists:prices,id'],
                'price.amount' => ['sometimes', 'min:0', 'numeric'],
                'price.interval' => ['sometimes', 'string', 'regex:(one-off|monthly)'],
                'price.interval_amount' => ['sometimes', 'nullable', 'min:1', 'max:1', 'numeric', 'integer'],
                'tags' => ['sometimes',],
                'tags.*.id' => ['required', 'string', 'exists:tags,id'],
                'tags.*.action' => ['required', 'string', 'regex:(add|remove)'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $user = $request->user();
            $digiverse = Collection::where('id', $id)->first();
            $digiverse->fill($request->only('title', 'description', 'is_available'));
            $digiverse->save();

            if (isset($request->price) && array_key_exists('id', $request->price)) {
                $price = $digiverse->prices()->where('id', $request->price['id'])->first();

                if (array_key_exists('amount', $request->price) && !is_null($request->price['amount'])) {
                    $price->amount = $request->price['amount'];
                }

                if (array_key_exists('interval', $request->price) && !is_null($request->price['interval'])) {
                    $price->interval = $request->price['interval'];
                }

                if (array_key_exists('interval_amount', $request->price) && !is_null($request->price['interval_amount'])) {
                    $price->interval_amount = $request->price['interval_amount'];
                }

                $price->save();
            }

            if (!is_null($request->cover) && array_key_exists('asset_id', $request->cover)) {
                $oldCover = $digiverse->cover()->first();
                $digiverse->cover()->detach($oldCover->id);
                $oldCover->delete();
                $digiverse->cover()->attach($request->cover['asset_id'], [
                    'id' => Str::uuid(),
                    'purpose' => 'cover'
                ]);
            }

            if (isset($request->tags) && is_array($request->tags)) {
                foreach ($request->tags as $tag) {
                    switch ($tag['action']) {
                        case 'add':
                            $digiverse->tags()->attach($tag['id'], [
                                'id' => Str::uuid(),
                            ]);
                            break;
                        case 'remove':
                            $digiverse->tags()->detach($tag['id']);
                            break;
                    }
                }
            }
            
            return $this->respondWithSuccess("Digiverse updated successfully.", [
                'digiverse' => new CollectionResource($digiverse),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function getAll(Request $request)
    {
        try {
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }
            $title = urldecode($request->query('title', ''));
            $categories = $request->query('categories', '');
            $types = $request->query('type','');
            $creators = $request->query('creators','');
            $freeItems = $request->query('free', 0);

                if ($categories != "") {
                    $categories = explode(",", urldecode($categories));
                    $categories = array_diff($categories, [""]);//get rid of empty values
                    $collections = Collection::whereHas('categories', function (Builder $query) use ($categories) {
                        $query->whereIn('name', $categories);
                    })->where('title', 'LIKE', '%' . $title . '%')->where('is_available', 1)->where('show_only_in_collections', 0)->where('approved_by_admin', 1);
                } else {
                    $collections = Collection::where('title', 'LIKE', '%' . $title . '%')->where('is_available', 1)->where('show_only_in_collections', 0)->where('approved_by_admin', 1);
                }
    
                if ($freeItems == 1) {
                    $collections = $collections->whereHas('prices', function (Builder $query) {
                        $query->where('amount', 0);
                    });
                }
    
                if ($types != "") {
                    $types = explode(",", urldecode($types));
                    $types = array_diff($types, [""]);//get rid of empty values
                    $collections = $collections->whereIn('type', $types);
                } 
    
                if ($creators != "") {
                    $creators = explode(",", urldecode($creators));
                    $creators = array_diff($creators, [""]);//get rid of empty values
                    $creators_id = User::whereIn('public_id', $creators)->pluck('id')->toArray();
                    $collections = $collections->whereIn('user_id', $creators_id );
                } 
    
                if ($request->user() == NULL || $request->user()->id == NULL) {
                    $user_id = 0;
                } else {
                    $user_id = $request->user()->id;
                }
    
                $collections = $collections->withCount([
                    'ratings' => function ($query) {
                        $query->where('rating', '>', 0);
                    }
                ])->withAvg([
                    'ratings' => function($query)
                    {
                        $query->where('rating', '>', 0);
                    }
                ], 'rating')->with('cover')->with('owner','owner.profile_picture')
                ->with('categories')
                ->with('prices', 'prices.continent', 'prices.country')
                ->with([
                    'userables' => function ($query) use ($user_id) {
                        $query->with('subscription')->where('user_id',  $user_id)->where('status', 'available');
                    },
                ])->orderBy('created_at', 'desc')->paginate($limit, array('*'), 'page', $page);

            return $this->respondWithSuccess("Collections retrieved successfully",[
                'collections' => CollectionResource::collection($collections),
                'current_page' => $collections->currentPage(),
                'items_per_page' => $collections->perPage(),
                'total' => $collections->total(),
            ]);

        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getUserCreatedCollections(Request $request, $user_public_id)
    {
        try {
            $user = User::where('public_id', $user_public_id)->first();
            if (is_null($user)) {
                return $this->respondBadRequest("Invalid user ID supplied");
            }
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }
            $title = urldecode($request->query('title', ''));
            $categories = $request->query('categories', '');
            $types = $request->query('type','');
            $freeItems = $request->query('free', 0);

                if ($categories != "") {
                    $categories = explode(",", urldecode($categories));
                    $categories = array_diff($categories, [""]);//get rid of empty values
                    $collections = $user->collectionsCreated()->whereHas('categories', function (Builder $query) use ($categories) {
                        $query->whereIn('name', $categories);
                    })->where('title', 'LIKE', '%' . $title . '%');
                } else {
                    $collections = $user->collectionsCreated()->where('title', 'LIKE', '%' . $title . '%');
                }
    
                if ($freeItems == 1) {
                    $collections = $collections->whereHas('prices', function (Builder $query) {
                        $query->where('amount', 0);
                    });
                }
    
                if ($request->user() == NULL || $request->user()->id == NULL) {
                    $collections = $collections->where('is_available', 1)->where('show_only_in_collections', 0)->where('approved_by_admin', 1);
                } else {
                    if ($request->user()->id !== $user->id) {
                        $collections = $collections->where('is_available', 1)->where('show_only_in_collections', 0)->where('approved_by_admin', 1);
                    }
                }
    
                if ($types != "") {
                    $types = explode(",", urldecode($types));
                    $types = array_diff($types, [""]);//get rid of empty values
                    $collections = $collections->whereIn('type', $types);
                } 
    
                $collections = $collections->withCount([
                    'ratings' => function ($query) {
                        $query->where('rating', '>', 0);
                    }
                ])->withAvg([
                    'ratings' => function($query)
                    {
                        $query->where('rating', '>', 0);
                    }
                ], 'rating')->with('cover')->with('approvalRequest')->with('owner','owner.profile_picture')->with('categories')->with('prices', 'prices.continent', 'prices.country')->orderBy('created_at', 'desc')->paginate($limit, array('*'), 'page', $page);
            
            return $this->respondWithSuccess("Collections retrieved successfully",[
                'collections' => CollectionResource::collection($collections),
                'current_page' => $collections->currentPage(),
                'items_per_page' => $collections->perPage(),
                'total' => $collections->total(),
            ]);

        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getReviews(Request $request, $public_id)
    {
        try {
            $collection = Collection::where('public_id', $public_id)->first();
            if (is_null($collection)) {
                return $this->respondBadRequest("Invalid collection ID supplied");
            }
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }

            $reviews = Cache::remember('request:collection#' . $collection->id . ':reviews:'  . url()->full(), Constants::MINUTE_CACHE_TIME * 5, function() use ($page, $limit, $request, $collection){
                $reviews = $collection->reviews()->with('user', 'user.profile_picture', 'user.roles')->orderBy('created_at', 'desc')->paginate($limit, array('*'), 'page', $page);
                return $reviews;
            });

            return $this->respondWithSuccess("Reviews retrieved successfully",[
                'reviews' => $reviews,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getSingle(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(['public_id' => $public_id], [
                'public_id' => ['required', 'string', 'exists:collections,public_id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            if ($request->user() == NULL || $request->user()->id == NULL) {
                $user_id = 0;
            } else {
                $user_id = $request->user()->id;
            }

            $collection = Collection::where('public_id', $public_id)
            ->withCount([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                }
            ])->withAvg([
                'ratings' => function($query)
                {
                    $query->where('rating', '>', 0);
                }
            ], 'rating')
            ->with('benefactors', 'benefactors.user')
            ->with('cover')
            ->with('approvalRequest')
            ->with('owner','owner.profile_picture')
            ->with('categories')
            ->with('prices', 'prices.continent', 'prices.country')
            ->with(['userables' => function ($query) use ($user_id) {
                $query->with('subscription')->where('user_id',  $user_id)->where('status', 'available');
            }])
            ->with('owner')
            ->with([
                'contents' => function ($query) {
                    $query->withCount([
                        'ratings' => function ($query) {
                            $query->where('rating', '>', 0);
                        }
                    ])
                    ->withAvg([
                        'ratings' => function($query)
                        {
                            $query->where('rating', '>', 0);
                        }
                    ], 'rating')
                    ->with('categories', 'owner', 'cover' ,'owner.profile_picture');
                }
            ])
            ->with([
                'childCollections' => function ($query) {
                    $query->withCount([
                        'ratings' => function ($query) {
                            $query->where('rating', '>', 0);
                        }
                    ])
                    ->withAvg([
                        'ratings' => function($query)
                        {
                            $query->where('rating', '>', 0);
                        }
                    ], 'rating')
                    ->with('categories', 'owner', 'cover' ,'owner.profile_picture');
                }
            ])
            ->first();

            return $this->respondWithSuccess("Collection retrieved successfully",[
                'collection' => new CollectionResource($collection),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }
}
