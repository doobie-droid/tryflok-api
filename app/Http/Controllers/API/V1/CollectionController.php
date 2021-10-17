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

    public function update(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $public_id]), [
                'id' => ['required', 'string', 'exists:collections,public_id'],
                'title' => ['sometimes', 'nullable', 'string'],
                'description' => ['sometimes', 'nullable', 'string'],
                'price.amount' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999.99'],
                'price.interval' => ['sometimes', 'nullable', 'string', 'regex:(one-off|month)'],
                'price.interval_amount' => ['sometimes', 'nullable','numeric', 'integer',],
                'type' => ['sometimes', 'nullable', 'string', 'regex:(book|series|channel|collectible)'],
                'contents' => ['sometimes','nullable'],
                'contents.*.public_id' => ['required', 'string', 'exists:contents,public_id'],
                'contents.*.action' => ['required', 'string', 'regex:(add|remove)'],
                'collections' => ['sometimes','nullable'],
                'collections.*.public_id' => ['required', 'string', 'exists:collections,public_id'],
                'collections.*.action' => ['required', 'string', 'regex:(add|remove)'],
                'categories' => ['sometimes', 'nullable'],
                'categories.*.public_id' => ['required', 'string', 'exists:categories,public_id'],
                'categories.*.action' => ['required', 'string', 'regex:(add|remove)'],
                'benefactors' => ['sometimes','nullable'],
                'benefactors.*.public_id' => ['required', 'exists:users,public_id'],
                'benefactors.*.action' => ['required', 'string', 'regex:(add|remove|update)'],
                'is_available' => ['sometimes', 'nullable', 'integer', 'regex:(0|1)'],
                'show_only_in_collections' => ['sometimes', 'nullable', 'integer', 'regex:(0|1)'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $user = $request->user();

            $collection = Collection::where('public_id', $public_id)->where('user_id', $user->id)->first();
            if (is_null($collection)) {
                return $this->respondBadRequest("You do not have permission to update this collection");
            }
            if (isset($request->benefactors) && is_array($request->benefactors)) {
                //validate benefactors
                $currentBenefactors = $collection->benefactors()->get();
                $benefactorsToAdd = [];
                $benefactorsToUpdate = [];
                $benefactorsToDelete = [];
                $sum = 0;
                foreach ($currentBenefactors as $benefactor) {
                    $found = false;
                    foreach ($request->benefactors as $requestBenefactor) {
                        //if found
                        if ($benefactor->user->public_id === $requestBenefactor['public_id']) {
                            $found = true;
                            if ($requestBenefactor['action'] === 'update') {
                                if (!array_key_exists('share', $requestBenefactor)){
                                    return $this->respondBadRequest("[share] is required if benefactor is being updated. Missing share detected for benefator with public_id [" . $requestBenefactor['public_id'] . "]" );
                                }
                                $benefactorsToUpdate[] = ['benefactor' => $benefactor, 'share' => $requestBenefactor['share']];
                                $sum = bcadd($sum,$requestBenefactor['share'],6);
                            }
                            if ($requestBenefactor['action'] === 'remove') {
                                $benefactorsToDelete[] = $benefactor;
                            }
                        }
                    }
                    if (!$found) {
                        $sum = bcadd($sum,$benefactor->share,6);
                    }
                }
                //loop through request benefactors and add the ones that are to be added
                foreach ($request->benefactors as $requestBenefactor) {
                    if ($requestBenefactor['action'] === 'add') {
                        if (!array_key_exists('share', $requestBenefactor)){
                            return $this->respondBadRequest("[share] is required if benefactor is being added. Missing share detected for benefator with public_id [" . $requestBenefactor['public_id'] . "]" );
                        }
                        $benefactorsToAdd[] = $requestBenefactor;
                        $sum = bcadd($sum,$requestBenefactor['share'],6);
                    }
                }

                $sum = (int) $sum;
                if ($sum !== 100) {
                    return $this->respondBadRequest("Benefactors sum does not add up to 100. " . $sum . " gotten instead" );
                }

                //update the updated benefactors
                foreach ($benefactorsToUpdate as $benefactorData) {
                    $benefactorData['benefactor']->share = $benefactorData['share'];
                    $benefactorData['benefactor']->save();
                }
                //remove the removed benefactors
                foreach ($benefactorsToDelete as $benefactor) {
                    $benefactor->delete();
                }
                //add the added benefactors
                foreach ($benefactorsToAdd as $benefactorData) {
                    $bUM = User::where('public_id', $benefactorData['public_id'])->first();
                    $benefactor = $collection->benefactors()->where('user_id', $bUM->id)->first();
                    if (is_null($benefactor)) {
                        $collection->benefactors()->create([
                            'user_id' => $bUM->id,
                            'share' => $benefactorData['share'],
                        ]);
                    } 
                }
            }
            

            if (!is_null($request->title)) {
                $collection->title = $request->title;
            }

            if (!is_null($request->description)) {
                $collection->description = $request->description;
            }

            if (!is_null($request->type)) {
                $collection->type = $request->type;
            }
            
            if (!is_null($request->is_available)) {
                $collection->is_available = $request->is_available;
            }

            if (!is_null($request->show_only_in_collections)) {
                $collection->show_only_in_collections = $request->show_only_in_collections;
            }
        
            $collection->save();

            

            //update categories
            if (isset($request->categories) && is_array($request->categories)) {
                foreach ($request->categories as $categoryData) {
                    $category = Category::where('public_id', $categoryData['public_id'])->first();
                    if ($categoryData['action'] === 'add') {
                        $collection->categories()->syncWithoutDetaching([$category->id]);
                    }
    
                    if ($categoryData['action'] === 'remove') {
                        $collection->categories()->detach($category->id);
                    }
                }
            }
            

            //update price
            $price = $collection->prices()->first();
            if (is_null($price)) {
                $price = $collection->prices()->create([
                    'public_id' => uniqid(rand()),
                    'amount' => 0,
                    'interval' => 'one-off',
                    'interval_amount' => 1,
                    'currency' => 'USD',
                ]);
            }
            if (isset($request->price) && is_array($request->price) && array_key_exists('amount', $request->price)) {
                $price->amount = $request->price['amount'];
            }

            if (isset($request->price) && is_array($request->price)  && array_key_exists('interval', $request->price)) {
                $price->interval = $request->price['interval'];
            }

            if (isset($request->price) && is_array($request->price)  && array_key_exists('interval_amount', $request->price) && $request->price['interval_amount'] > 0) {
                $price->interval_amount = $request->price['interval_amount'];
            }
            $price->save();

            //update contents
            $new_contents = [];
            $deleted_contents = [];
            if (isset($request->contents) && is_array($request->contents))  {
                foreach ($request->contents as $contentsData) {
                    $content = Content::where('public_id', $contentsData['public_id'])->first();
                    if ($contentsData['action'] === 'add') {
                        $collection->contents()->syncWithoutDetaching([$content->id]);
                        $new_contents[] = $content;
                    }
    
                    if ($contentsData['action'] === 'remove') {
                        $collection->contents()->detach($content->id);
                        $deleted_contents[] = $content;
                    }
                }
            }
            
            
            $new_collections = [];
            $deleted_collections = [];
            if (isset($request->collections) && is_array($request->collections)) {
                //update collections
                foreach ($request->collections as $collectionsData) {
                    $childCollection = Collection::where('public_id', $collectionsData['public_id'])->first();
                    if (!is_null($childCollection)) {
                        if ($collectionsData['action'] === 'add') {
                            $collection->childCollections()->syncWithoutDetaching([$childCollection->id]);
                            $new_collections[] = $childCollection;
                        }
    
                        if ($collectionsData['action'] === 'remove') {
                            $collection->childCollections()->detach($childCollection->id);
                            $deleted_collections[] = $childCollection;
                        }
                    }
                }
            }

            //update userables
            DispatchContentUserablesUpdateJob::dispatch([
                'collection' => $collection,
                'deleted_contents' => $deleted_contents,
                'new_contents' => $new_contents,
            ]);

            DispatchCollectionUserablesUpdateJob::dispatch([
                'collection' => $collection,
                'deleted_collections' => $deleted_collections,
                'new_collections' => $new_collections,
            ]);
            $collection->cover = $collection->cover();
            $collection->contents = $collection->contents()->get();
            $collection->collections = $collection->childCollections()->get();
            $collection->prices = $collection->prices()->get();
            $collection->categories = $collection->categories()->get();
            $collection->benefactors = $collection->benefactors()->with('user')->get();
            return $this->respondWithSuccess("Collection has been updated successfully.", [
                'collection' => new CollectionResource($collection),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function updateCover(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $public_id]), [
                'id' => ['required', 'string', 'exists:collections,public_id'],
                'cover' => ['required', 'image'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $collection = Collection::where('public_id', $public_id)->where('user_id', $request->user()->id)->first();
            if (is_null($collection)) {
                return $this->respondBadRequest("You do not have permission to update this collection");
            }

            $storage = new Storage('cloudinary');
            if ($request->hasFile('cover')) {
                $oldCover = $collection->assets()->where('purpose', 'cover')->first();
                if (!is_null($oldCover)) {
                    $storage = new Storage($oldCover->storage_provider);
                    $storage->delete($oldCover->storage_provider_id);
                    $oldCover->forceDelete();
                }
                $response = $storage->upload($request->file('cover')->getRealPath(), 'collections/' . $collection->public_id . '/cover');
                $collection->assets()->create([
                    'public_id' => uniqid(rand()),
                    'storage_provider' => 'cloudinary',
                    'storage_provider_id' => $response['storage_provider_id'],
                    'url' => $response['url'],
                    'purpose' => 'cover',
                    'asset_type' => 'image',
                    'mime_type' => $request->file('cover')->getMimeType(),
                ]);
            }

            return $this->respondWithSuccess("Cover has been created successfully.", [
                'collection' => new CollectionResource($collection),
                'cover' => $collection->cover(),
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
