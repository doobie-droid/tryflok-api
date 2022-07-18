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
                'price' => ['required'],
                'price.amount' => ['required', 'min:0', 'numeric', 'max:10000'],
                'price.interval' => ['required', 'string', 'in:one-off,monthly'],
                'price.interval_amount' => ['required','min:1', 'max:1', 'numeric', 'integer'],
                'tags' => ['sometimes'],
                'tags.*' => ['required', 'string', 'exists:tags,id'],
                'is_challenge' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $user = $request->user();

            $is_challenge = 0;
            if (isset($request->is_challenge) && (int) $request->is_challenge === 1) {
                $is_challenge = 1;
            }

            $digiverse = Collection::create([
                'title' => $request->title,
                'description' => $request->description,
                'user_id' => $user->id,
                'type' => 'digiverse',
                'is_available' => 1,
                'approved_by_admin' => 1,
                'show_only_in_collections' => 0,
                'views' => 0,
                'is_challenge' => $is_challenge,
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
            ->eagerLoadBaseRelations()
            ->first();

            return $this->respondWithSuccess('Digiverse has been created successfully.', [
                'digiverse' => new CollectionResource($digiverse),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function createCollection(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'digiverse_id' => ['required','exists:collections,id'],
                'title' => ['required', 'string'],
                'description' => ['required', 'string'],
                'cover.asset_id' => ['required', 'string', 'exists:assets,id', new AssetTypeRule('image')],
                'price' => ['required'],
                'price.amount' => ['required', 'min:0', 'numeric', 'max:10000'],
                'tags' => ['sometimes'],
                'tags.*' => ['required', 'string', 'exists:tags,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $user = $request->user();
            $digiverse = Collection::where('id', $request->digiverse_id)->where('type', 'digiverse')->first();
            if (is_null($digiverse)) {
                return $this->respondBadRequest("The collection with ID {$request->digiverse_id} is not a digiverse");
            }

            if ($digiverse->user_id !== $user->id) {
                return $this->respondBadRequest('You cannot to this digiverse because you do not own it');
            }

            $collection = Collection::create([
                'title' => $request->title,
                'description' => $request->description,
                'user_id' => $user->id,
                'type' => 'collection',
                'is_available' => 1,
                'approved_by_admin' => 1,
                'show_only_in_collections' => 0,
                'views' => 0,
                'is_challenge' => 0,
            ]);

            $digiverse->childCollections()->attach($collection->id, [
                'id' => Str::uuid(),
            ]);

            $collection->benefactors()->create([
                'user_id' => $user->id,
                'share' => 100,
            ]);

            $collection->prices()->create([
                'amount' => $request->price['amount'],
                'interval' => 'one-off',
                'interval_amount' => 1,
            ]);

            if (isset($request->tags) && is_array($request->tags)) {
                foreach ($request->tags as $tag_id) {
                    $collection->tags()->attach($tag_id, [
                        'id' => Str::uuid(),
                    ]);
                }
            }

            $collection->cover()->attach($request->cover['asset_id'], [
                'id' => Str::uuid(),
                'purpose' => 'cover',
            ]);

            $collection = Collection::where('id', $digiverse->id)
            ->eagerLoadBaseRelations()
            ->first();

            return $this->respondWithSuccess('Collection has been created successfully.', [
                'collection' => new CollectionResource($collection),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function showDigiverse(Request $request, $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'string', 'exists:collections,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            $digiverse = Collection::where('id', $id)
            ->eagerLoadBaseRelations($user_id)
            ->first();
            $digiverse->content_types_available = $digiverse->contentTypesAvailable($user_id);
            return $this->respondWithSuccess('Digiverse retrieved successfully.', [
                'digiverse' => new CollectionResource($digiverse),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function showCollection(Request $request, $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'string', 'exists:collections,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            $collection = Collection::where('id', $id)
            ->eagerLoadBaseRelations($user_id)
            ->first();
            $collection->content_types_available = $collection->contentTypesAvailable($user_id);
            return $this->respondWithSuccess('Collection retrieved successfully.', [
                'collection' => new CollectionResource($collection),
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
                'price' => ['sometimes', 'nullable'],
                'price.id' => ['sometimes', 'exists:prices,id'],
                'price.amount' => ['sometimes', 'min:0', 'numeric', 'max:1000'],
                'price.interval' => ['sometimes', 'string', 'in:one-off,monthly'],
                'price.interval_amount' => ['sometimes', 'nullable', 'integer', 'size:1'],
                'tags' => ['sometimes'],
                'tags.*.id' => ['required', 'string', 'exists:tags,id'],
                'tags.*.action' => ['required', 'string', 'in:add,remove'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $user = $request->user();
            $digiverse = Collection::where('id', $id)
            ->eagerLoadBaseRelations()
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

            if (! is_null($request->cover) && array_key_exists('asset_id', $request->cover) && ! is_null($request->cover['asset_id']) && $request->cover['asset_id'] != '') {
                $oldCover = $digiverse->cover()->first();
                if (! is_null($oldCover)) {
                    $digiverse->cover()->detach($oldCover->id);
                    $oldCover->delete();
                }
                
                $digiverse->cover()->attach($request->cover['asset_id'], [
                    'id' => Str::uuid(),
                    'purpose' => 'cover',
                ]);
            }

            if (isset($request->tags) && is_array($request->tags)) {
                foreach ($request->tags as $tag) {
                    switch ($tag['action']) {
                        case 'add':
                            $digiverse->tags()->syncWithoutDetaching([
                                $tag['id'] => [
                                    'id' => Str::uuid(),
                                ],
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

    public function addOrRemoveContent(Request $request, $id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:collections,id'],
                'contents' => ['required'],
                'contents.*.id' => ['required', 'string', 'exists:contents,id'],
                'contents.*.action' => ['required', 'string', 'in:add,remove'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $collection = Collection::where('id', $id)
            ->eagerLoadBaseRelations($user_id)
            ->first();

            if (! is_null($request->contents) && is_array($request->contents)) {
                foreach ($request->contents as $contentData) {
                    if ($contentData['action'] === 'add') {
                        $collection->contents()
                        ->syncWithoutDetaching([
                            $contentData['id'] => [
                                'id' => Str::uuid(),
                            ],
                        ]);
                    }

                    if ($contentData['action'] === 'remove') {
                        $collection->contents()->detach($contentData['id']);
                    }
                }
            }
            
            $collection->content_types_available = $collection->contentTypesAvailable($user_id);
            return $this->respondWithSuccess('Collection contents updated successfully.', [
                'collection' => new CollectionResource($collection),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
    public function updateCollection(Request $request, $id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:collections,id'],
                'title' => ['sometimes', 'nullable', 'string'],
                'description' => ['sometimes', 'nullable', 'string'],
                'cover.asset_id' => ['sometimes', 'nullable', 'string', 'exists:assets,id', new AssetTypeRule('image')],
                'is_available' => ['sometimes', 'integer', 'min:0', 'max:1'],
                'price' => ['sometimes', 'nullable'],
                'price.amount' => ['sometimes', 'min:0', 'numeric', 'max:1000'],
                'tags' => ['sometimes'],
                'tags.*.id' => ['required', 'string', 'exists:tags,id'],
                'tags.*.action' => ['required', 'string', 'in:add,remove'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $user = $request->user();
            $collection = Collection::where('id', $id)
            ->eagerLoadBaseRelations()
            ->first();
            $collection->fill($request->only('title', 'description', 'is_available'));
            $collection->save();

            if (isset($request->price) && array_key_exists('id', $request->price)) {
                $price = $collection->prices()->first();

                if (array_key_exists('amount', $request->price) && ! is_null($request->price['amount'])) {
                    $price->amount = $request->price['amount'];
                }
                $price->save();
            }

            if (! is_null($request->cover) && array_key_exists('asset_id', $request->cover) && ! is_null($request->cover['asset_id']) && $request->cover['asset_id'] != '') {
                $oldCover = $collection->cover()->first();
                if (! is_null($oldCover)) {
                    $collection->cover()->detach($oldCover->id);
                    $oldCover->delete();
                }
                
                $collection->cover()->attach($request->cover['asset_id'], [
                    'id' => Str::uuid(),
                    'purpose' => 'cover',
                ]);
            }

            if (isset($request->tags) && is_array($request->tags)) {
                foreach ($request->tags as $tag) {
                    switch ($tag['action']) {
                        case 'add':
                            $collection->tags()->syncWithoutDetaching([
                                $tag['id'] => [
                                    'id' => Str::uuid(),
                                ],
                            ]);
                            break;
                        case 'remove':
                            $collection->tags()->detach($tag['id']);
                            break;
                    }
                }
            }

            return $this->respondWithSuccess('Collection updated successfully.', [
                'collection' => new CollectionResource($collection),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function listDigiverses(Request $request)
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
                'page' => ['required', 'integer', 'min:1'],
                'limit' => ['required', 'integer', 'min:1', "max:{$max_items_count}"],
                'keyword' => ['sometimes', 'string', 'max:200'],
                'max_price' => ['required', 'integer', 'min:-1'],
                'min_price' => ['required', 'integer', 'min:0'],
                'order_by' => ['required', 'string', 'in:created_at,price,views,reviews'],
                'order_direction' => ['required', 'string', 'in:asc,desc'],
                'tags' => ['sometimes'],
                'tags.*' => ['required', 'string', 'exists:tags,id'],
                'creators' => ['sometimes'],
                'creators.*' => ['required', 'string', 'exists:users,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $digiverses = Collection::where('type', 'digiverse')
            ->where('is_available', 1)
            ->where('approved_by_admin', 1)
            ->where('is_adult', 0)
            ->where('show_only_in_collections', 0)
            ->whereHas('contents', function (Builder $query) {
                $query->where('is_available', 1)->where('approved_by_admin', 1);
            });

            if (! empty($keywords)) {
                $digiverses = $digiverses->where(function ($query) use ($keywords) {
                    $query->where('title', 'LIKE', "%{$keywords[0]}%")
                    ->orWhere('description', 'LIKE', "%{$keywords[0]}%");
                    for ($i = 1; $i < count($keywords); $i++) {
                        $query->orWhere('title', 'LIKE', "%{$keywords[$i]}%")
                            ->orWhere('description', 'LIKE', "%{$keywords[$i]}%");
                    }
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
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            $digiverses = $digiverses
            ->eagerLoadBaseRelations($user_id)
            ->orderBy('collections.created_at', 'desc')
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

    public function listDigiverseCollections(Request $request, $digiverse_id)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);

            $keyword = urldecode($request->query('keyword', ''));
            $keywords = explode(' ', $keyword);
            $keywords = array_diff($keywords, ['']);

            $types = $request->query('types', '');
            $types = explode(',', urldecode($types));
            $types = array_diff($types, ['']);

            $tags = $request->query('tags', '');
            $tags = explode(',', urldecode($tags));
            $tags = array_diff($tags, ['']);

            $creators = $request->query('creators', '');
            $creators = explode(',', urldecode($creators));
            $creators = array_diff($creators, ['']);

            $maxPrice = $request->query('max_price', -1);
            $minPrice = $request->query('min_price', 0);

            $orderBy = $request->query('order_by', 'created_at');
            $orderDirection = $request->query('order_direction', 'desc');

            $activeLiveContent = $request->query('active_live_content', 'false');

            $max_items_count = Constants::MAX_ITEMS_LIMIT;
            $validator = Validator::make([
                'id' => $digiverse_id,
                'page' => $page,
                'limit' => $limit,
                'keyword' => $keyword,
                'tags' => $tags,
                'creators' => $creators,
                'max_price' => $maxPrice,
                'min_price' => $minPrice,
                'order_by' => $orderBy,
                'order_direction' => $orderDirection,
                'type' => $types,
            ], [
                'id' => ['required', 'string', 'exists:collections,id'],
                'page' => ['required', 'integer', 'min:1'],
                'limit' => ['required', 'integer', 'min:1', "max:{$max_items_count}"],
                'keyword' => ['sometimes', 'string', 'max:200'],
                'max_price' => ['required', 'integer', 'min:-1'],
                'min_price' => ['required', 'integer', 'min:0'],
                'order_by' => ['required', 'string', 'in:created_at,price,views,reviews,scheduled_date'],
                'order_direction' => ['required', 'string', 'in:asc,desc'],
                'types' => ['sometimes'],
                'type.*' => ['required', 'string'],
                'tags' => ['sometimes'],
                'tags.*' => ['required', 'string', 'exists:tags,id'],
                'creators' => ['sometimes'],
                'creators.*' => ['required', 'string', 'exists:users,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
            $digiverse = Collection::where('id', $request->digiverse_id)->first();
            $collections = $digiverse->collections()->whereNull('archived_at');

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            if ($user_id !== $digiverse->user_id) {
                $collections = $collections->where('is_available', 1)->where('approved_by_admin', 1);
            }

            if (! empty($keywords)) {
                $collections = $collections->where(function ($query) use ($keywords) {
                    $query->where('title', 'LIKE', "%{$keywords[0]}%")
                    ->orWhere('description', 'LIKE', "%{$keywords[0]}%");
                    for ($i = 1; $i < count($keywords); $i++) {
                        $query->orWhere('title', 'LIKE', "%{$keywords[$i]}%")
                            ->orWhere('description', 'LIKE', "%{$keywords[$i]}%");
                    }
                });
            }

            if (! empty($types)) {
                $collections = $collections->whereIn('type', $types);
            }

            if (! empty($tags)) {
                $collections = $collections->whereHas('tags', function (Builder $query) use ($tags) {
                    $query->whereIn('tags.id', $tags);
                });
            }

            if (! empty($creators)) {
                $collections = $collections->whereIn('user_id', $creators);
            }

            $collections = $collections
            ->eagerLoadBaseRelations($user_id)
            ->orderBy("collections.{$orderBy}", $orderDirection)
            ->paginate($limit, ['*'], 'page', $page);

            return $this->respondWithSuccess('Collections retrieved successfully', [
                'collections' => CollectionResource::collection($collections),
                'current_page' => (int) $collections->currentPage(),
                'items_per_page' => (int) $collections->perPage(),
                'total' => (int) $collections->total(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function listUserCreatedDigiverses(Request $request)
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
                'page' => ['required', 'integer', 'min:1'],
                'limit' => ['required', 'integer', 'min:1', "max:{$max_items_count}"],
                'keyword' => ['sometimes', 'string', 'max:200'],
                'max_price' => ['required', 'integer', 'min:-1'],
                'min_price' => ['required', 'integer', 'min:0'],
                'order_by' => ['required', 'string', 'in:created_at,price,views,reviews'],
                'order_direction' => ['required', 'string', 'in:asc,desc'],
                'tags' => ['sometimes'],
                'tags.*' => ['required', 'string', 'exists:tags,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $digiverses = Collection::where('type', 'digiverse')->where('user_id', $request->user()->id);

            if (! empty($keywords)) {
                $digiverses = $digiverses->where(function ($query) use ($keywords) {
                    $query->where('title', 'LIKE', "%{$keywords[0]}%")
                    ->orWhere('description', 'LIKE', "%{$keywords[0]}%");
                    for ($i = 1; $i < count($keywords); $i++) {
                        $query->orWhere('title', 'LIKE', "%{$keywords[$i]}%")
                            ->orWhere('description', 'LIKE', "%{$keywords[$i]}%");
                    }
                });
            }

            if (! empty($tags)) {
                $digiverses = $digiverses->whereHas('tags', function (Builder $query) use ($tags) {
                    $query->whereIn('tags.id', $tags);
                });
            }

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            $digiverses = $digiverses
            ->eagerLoadBaseRelations($user_id)
            ->orderBy('collections.created_at', 'desc')
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

    public function listReviews(Request $request, $id)
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

    public function archive(Request $request, $id)
    {
        try {
            //make sure user owns content
            $collection = Collection::where('id', $id)->where('user_id', $request->user()->id)
            ->eagerLoadBaseRelations()
            ->first();

            if (is_null($collection)) {
                return $this->respondBadRequest('You do not have permission to update this collection');
            }

            $collection->archived_at = now();
            $collection->save();
            return $this->respondWithSuccess('Collection has been archived successfully', [
                'collection' => new CollectionResource($collection),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function delete(Request $request, $id)
    {
        try {
            //make sure user owns content
            $collection = Collection::where('id', $id)->where('user_id', $request->user()->id)
            ->eagerLoadBaseRelations()
            ->first();
            if (is_null($collection)) {
                return $this->respondBadRequest('You do not have permission to delete this collection');
            }

            // make sure there are no active purchases
            $active_purchases = $collection->userables()->where('status', 'available')->count();
            if ($active_purchases > 0) {
                return $this->respondBadRequest('You cannot delete a collection that has active purchases. Archive the collection instead');
            }

            $collection->delete();
            return $this->respondWithSuccess('Collection deleted successfully', [
                'collection' => new CollectionResource($collection),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
