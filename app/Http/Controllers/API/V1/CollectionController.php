<?php

namespace App\Http\Controllers\API\V1;

use App\Constants\Constants;
use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use App\Rules\AssetType as AssetTypeRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    public function createDigiverse(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => ['required', 'string'],
                'description' => ['required', 'string'],
                'cover.asset_id' => ['required', 'string', 'exists:assets,id', new AssetTypeRule('image')],
                'price' => ['required',],
                'price.amount' => ['required', 'min:0', 'numeric'],
                'price.interval' => ['required', 'string', 'regex:(one-off|monthly)'],
                'price.interval_amount' => ['required','min:1', 'max:1', 'numeric', 'integer'],
                'tags' => ['sometimes',],
                'tags.*' => ['required', 'string', 'exists:tags,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
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

            $digiverse->cover()->attach($request->cover['asset_id'], [
                'id' => Str::uuid(),
                'purpose' => 'cover',
            ]);

            $digiverse = Collection::where('id', $digiverse->id)
            ->with('prices', 'cover', 'owner', 'owner.profile_picture', 'tags')
            ->withCount([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                },
            ])->withAvg([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                },
            ], 'rating')
            ->first();

            return $this->respondWithSuccess('Digiverse has been created successfully.', [
                'digiverse' => new CollectionResource($digiverse),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getDigiverse(Request $request, $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'string', 'exists:collections,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = 0;
            } else {
                $user_id = $request->user()->id;
            }

            $digiverse = Collection::where('id', $id)
            ->with('prices', 'cover', 'owner', 'owner.profile_picture', 'tags')
            ->withCount([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                },
            ])->withAvg([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                },
            ], 'rating')
            ->with([
                'userables' => function ($query) use ($user_id) {
                    $query->with('subscription')->where('user_id', $user_id)->where('status', 'available');
                },
            ])
            ->first();
            $digiverse->content_types_available = $digiverse->contentTypesAvailable();
            return $this->respondWithSuccess('Digiverse retrieved successfully.', [
                'digiverse' => new CollectionResource($digiverse),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
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
                'is_available' => ['sometimes', 'integer', 'min:0', 'max:1'],
                'price' => ['sometimes', 'nullable',],
                'price.id' => ['sometimes', 'exists:prices,id'],
                'price.amount' => ['sometimes', 'min:0', 'numeric'],
                'price.interval' => ['sometimes', 'string', 'regex:(one-off|monthly)'],
                'price.interval_amount' => ['sometimes', 'nullable', 'integer', 'size:1',],
                'tags' => ['sometimes',],
                'tags.*.id' => ['required', 'string', 'exists:tags,id'],
                'tags.*.action' => ['required', 'string', 'regex:(add|remove)'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $user = $request->user();
            $digiverse = Collection::where('id', $id)
            ->with('prices', 'cover', 'owner', 'tags')
            ->withCount([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                },
            ])->withAvg([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                },
            ], 'rating')
            ->first();
            $digiverse->fill($request->only('title', 'description', 'is_available'));
            $digiverse->save();

            if (isset($request->price) && array_key_exists('id', $request->price)) {
                $price = $digiverse->prices()->where('id', $request->price['id'])->first();

                if (array_key_exists('amount', $request->price) && ! is_null($request->price['amount'])) {
                    $price->amount = $request->price['amount'];
                }

                if (array_key_exists('interval', $request->price) && ! is_null($request->price['interval'])) {
                    $price->interval = $request->price['interval'];
                }

                if (array_key_exists('interval_amount', $request->price) && ! is_null($request->price['interval_amount'])) {
                    $price->interval_amount = $request->price['interval_amount'];
                }

                $price->save();
            }

            if (! is_null($request->cover) && array_key_exists('asset_id', $request->cover)) {
                $oldCover = $digiverse->cover()->first();
                $digiverse->cover()->detach($oldCover->id);
                $oldCover->delete();
                $digiverse->cover()->attach($request->cover['asset_id'], [
                    'id' => Str::uuid(),
                    'purpose' => 'cover',
                ]);
            }

            if (isset($request->tags) && is_array($request->tags)) {
                foreach ($request->tags as $tag) {
                    switch ($tag['action']) {
                        case 'add':
                            $digiverse->tags()->syncWithoutDetaching($tag['id'], [
                                'id' => Str::uuid(),
                            ]);
                            break;
                        case 'remove':
                            $digiverse->tags()->detach($tag['id']);
                            break;
                    }
                }
            }

            return $this->respondWithSuccess('Digiverse updated successfully.', [
                'digiverse' => new CollectionResource($digiverse),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getAll(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);

            $keyword = urldecode($request->query('keyword', ''));
            $keywords = explode(' ', $keyword);
            $keywords = array_diff($keywords, ['']);

            $tags = $request->query('tags', '');
            $tags = explode(',', urldecode($tags));
            $tags = array_diff($tags, ['']);

            $creators = $request->query('creators', '');
            $creators = explode(',', urldecode($creators));
            $creators = array_diff($creators, ['']);

            $maxPrice = $request->query('max_price', -1);
            $minPrice = $request->query('min_price', 0);

            $orderBy = $request->query('order_by', 'created_at');
            $orderDirection = $request->query('order_direction', 'asc');

            $max_items_count = Constants::MAX_ITEMS_LIMIT;
            $validator = Validator::make([
                'page' => $page,
                'limit' => $limit,
                'keyword' => $keyword,
                'tags' => $tags,
                'creators' => $creators,
                'max_price' => $maxPrice,
                'min_price' => $minPrice,
                'order_by' => $orderBy,
                'order_direction' => $orderDirection,
            ], [
                'page' => ['required', 'integer', 'min:1',],
                'limit' => ['required', 'integer', 'min:1', "max:{$max_items_count}",],
                'keyword' => ['sometimes', 'string', 'max:200',],
                'max_price' => ['required', 'integer', 'min:-1',],
                'min_price' => ['required', 'integer', 'min:0',],
                'order_by' => ['required', 'string', 'regex:(created_at|price|views|reviews)'],
                'order_direction' => ['required', 'string', 'regex:(asc|desc)'],
                'tags' => ['sometimes',],
                'tags.*' => ['required', 'string', 'exists:tags,id',],
                'creators' => ['sometimes',],
                'creators.*' => ['required', 'string', 'exists:users,id',],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $digiverses = Collection::where('type', 'digiverse')->where('is_available', 1)
            ->where('show_only_in_collections', 0);

            foreach ($keywords as $keyword) {
                $digiverses = $digiverses->where(function ($query) use ($keyword) {
                    $query->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('description', 'LIKE', "%{$keyword}%");
                });
            }

            if (! empty($tags)) {
                $digiverses = $digiverses->whereHas('tags', function (Builder $query) use ($tags) {
                    $query->whereIn('tags.id', $tags);
                });
            }

            if (! empty($creators)) {
                $digiverses = $digiverses->whereIn('user_id', $creators);
            }

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = 0;
            } else {
                $user_id = $request->user()->id;
            }

            $digiverses = $digiverses
            ->withCount([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                },
            ])->withAvg([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                },
            ], 'rating')
            ->with('cover')
            ->with('owner', 'owner.profile_picture')
            ->with('tags')
            ->with('prices')
            ->with([
                'userables' => function ($query) use ($user_id) {
                    $query->with('subscription')->where('user_id', $user_id)->where('status', 'available');
                },
            ])->orderBy('collections.created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

            return $this->respondWithSuccess('Digiverses retrieved successfully', [
                'digiverses' => CollectionResource::collection($digiverses),
                'current_page' => (int) $digiverses->currentPage(),
                'items_per_page' => (int) $digiverses->perPage(),
                'total' => (int) $digiverses->total(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getUserCreatedDigiverses(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);

            $keyword = urldecode($request->query('keyword', ''));
            $keywords = explode(' ', $keyword);
            $keywords = array_diff($keywords, ['']);

            $tags = $request->query('tags', '');
            $tags = explode(',', urldecode($tags));
            $tags = array_diff($tags, ['']);

            $maxPrice = $request->query('max_price', -1);
            $minPrice = $request->query('min_price', 0);

            $orderBy = $request->query('order_by', 'created_at');
            $orderDirection = $request->query('order_direction', 'asc');

            $max_items_count = Constants::MAX_ITEMS_LIMIT;
            $validator = Validator::make([
                'page' => $page,
                'limit' => $limit,
                'keyword' => $keyword,
                'tags' => $tags,
                'max_price' => $maxPrice,
                'min_price' => $minPrice,
                'order_by' => $orderBy,
                'order_direction' => $orderDirection,
            ], [
                'page' => ['required', 'integer', 'min:1',],
                'limit' => ['required', 'integer', 'min:1', "max:{$max_items_count}",],
                'keyword' => ['sometimes', 'string', 'max:200',],
                'max_price' => ['required', 'integer', 'min:-1',],
                'min_price' => ['required', 'integer', 'min:0',],
                'order_by' => ['required', 'string', 'regex:(created_at|price|views|reviews)'],
                'order_direction' => ['required', 'string', 'regex:(asc|desc)'],
                'tags' => ['sometimes',],
                'tags.*' => ['required', 'string', 'exists:tags,id',],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $digiverses = Collection::where('type', 'digiverse')->where('user_id', $request->user()->id);

            foreach ($keywords as $keyword) {
                $digiverses = $digiverses->where(function ($query) use ($keyword) {
                    $query->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('description', 'LIKE', "%{$keyword}%");
                });
            }

            if (! empty($tags)) {
                $digiverses = $digiverses->whereHas('tags', function (Builder $query) use ($tags) {
                    $query->whereIn('tags.id', $tags);
                });
            }

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = 0;
            } else {
                $user_id = $request->user()->id;
            }

            $digiverses = $digiverses
            ->withCount([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                },
            ])->withAvg([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                },
            ], 'rating')
            ->with('cover')
            ->with('owner', 'owner.profile_picture')
            ->with('tags')
            ->with('prices')
            ->with([
                'userables' => function ($query) use ($user_id) {
                    $query->with('subscription')->where('user_id', $user_id)->where('status', 'available');
                },
            ])->orderBy('collections.created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

            return $this->respondWithSuccess('Digiverses retrieved successfully', [
                'digiverses' => CollectionResource::collection($digiverses),
                'current_page' => (int) $digiverses->currentPage(),
                'items_per_page' => (int) $digiverses->perPage(),
                'total' => (int) $digiverses->total(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getReviews(Request $request, $id)
    {
        try {
            $collection = Collection::where('id', $id)->first();
            if (is_null($collection)) {
                return $this->respondBadRequest('Invalid collection ID supplied');
            }
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }

            $reviews = Cache::remember('request:collection#' . $collection->id . ':reviews:' . url()->full(), Constants::MINUTE_CACHE_TIME * 5, function () use ($page, $limit, $request, $collection) {
                $reviews = $collection->reviews()->with('user', 'user.profile_picture', 'user.roles')->orderBy('created_at', 'desc')->paginate($limit, ['*'], 'page', $page);
                return $reviews;
            });

            return $this->respondWithSuccess('Reviews retrieved successfully', [
                'reviews' => $reviews,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getSingle(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(['public_id' => $public_id], [
                'public_id' => ['required', 'string', 'exists:collections,public_id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = 0;
            } else {
                $user_id = $request->user()->id;
            }

            $collection = Collection::where('public_id', $public_id)
            ->withCount([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                },
            ])->withAvg([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                },
            ], 'rating')
            ->with('benefactors', 'benefactors.user')
            ->with('cover')
            ->with('approvalRequest')
            ->with('owner', 'owner.profile_picture')
            ->with('categories')
            ->with('prices', 'prices.continent', 'prices.country')
            ->with([
                'userables' => function ($query) use ($user_id) {
                    $query->with('subscription')->where('user_id', $user_id)->where('status', 'available');
                },
            ])
            ->with('owner')
            ->with([
                'contents' => function ($query) {
                    $query->withCount([
                        'ratings' => function ($query) {
                            $query->where('rating', '>', 0);
                        },
                    ])
                    ->withAvg([
                        'ratings' => function ($query) {
                            $query->where('rating', '>', 0);
                        },
                    ], 'rating')
                    ->with('categories', 'owner', 'cover', 'owner.profile_picture');
                },
            ])
            ->with([
                'childCollections' => function ($query) {
                    $query->withCount([
                        'ratings' => function ($query) {
                            $query->where('rating', '>', 0);
                        },
                    ])
                    ->withAvg([
                        'ratings' => function ($query) {
                            $query->where('rating', '>', 0);
                        },
                    ], 'rating')
                    ->with('categories', 'owner', 'cover', 'owner.profile_picture');
                },
            ])
            ->first();

            return $this->respondWithSuccess('Collection retrieved successfully', [
                'collection' => new CollectionResource($collection),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
