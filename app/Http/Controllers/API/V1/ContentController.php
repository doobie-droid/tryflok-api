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
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Price;
use App\Models\Review;
use App\Models\Userable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use App\Rules\AssetType as AssetTypeRule;
use App\Http\Resources\ContentResource;

class ContentController extends Controller
{
    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => ['required', 'string', 'max: 200'],
                'description' => ['sometimes', 'string'],
                'digiverse_id' => ['required','exists:collections,id'],
                'cover.asset_id' => ['required', 'string', 'exists:assets,id', new AssetTypeRule('image')],
                'price' => ['required',],
                'price.amount' => ['required', 'min:0', 'numeric'],
                'tags' => ['sometimes',],
                'tags.*' => ['required', 'string', 'exists:tags,id'],
                'type' => ['required', 'string', 'regex:(pdf|audio|video|newsletter|live-audio|live-video)'],
                'asset_id' => ['required_if:type,pdf,audio,video', 'nullable', 'exists:assets,id', new AssetTypeRule($request->type)],
                'is_available' => ['required', 'integer', 'min:0', 'max:1'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $digiverse = Collection::where('id', $request->digiverse_id)->where('type', 'digiverse')->first();
            if (is_null($digiverse)) {
                return $this->respondBadRequest("The collection with ID {$request->digiverse_id} is not a digiverse");
            }

            if ($request->type === 'newsletter') {
                $digiverseNewsletterCount = $digiverse->contents()->where('type', 'newsletter')->count();
                if ($digiverseNewsletterCount > 0) {
                    return $this->respondBadRequest("This Digiverse already has a newsletter. Only one newsletter allowed per digiverse.");
                }
            }
            $user = $request->user();
            $content = Content::create([
                'title' => $request->title,
                'description' => $request->description,
                'user_id' => $user->id,
                'type' => $request->type,
                'is_available' => $request->is_available,
                'approved_by_admin' => 0,
                'show_only_in_digiverses' => 1,
                'views' => 0,
            ]);

            $digiverse->contents()->attach($content->id, [
                'id' => Str::uuid(),
            ]);

            $content->benefactors()->create([
                'user_id' => $user->id,
                'share' => 100,
            ]);

            $content->prices()->create([
                'amount' => $request->price['amount'],
                'interval' => 'one-off',
                'interval_amount' => 1,
            ]);

            $content->cover()->attach($request->cover['asset_id'], [
                'id' => Str::uuid(),
                'purpose' => 'cover',
            ]);
            
            if (!is_null($request->asset_id)) {
                $content->assets()->attach($request->asset_id, [
                    'id' => Str::uuid(),
                    'purpose' => 'content-asset',
                ]);
            }
            
            if (isset($request->tags) && is_array($request->tags)) {
                foreach ($request->tags as $tag_id) {
                    $content->tags()->attach($tag_id, [
                        'id' => Str::uuid(),
                    ]);
                }
            }
            return $this->respondWithSuccess("Content has been created successfully", [
                'content' => new ContentResource($content),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function update(Request $request, $id)
    {
        try {
            $validator1 = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:contents,id'],
                'title' => ['sometimes', 'nullable', 'string', 'max:200', 'min:1'],
                'description' => ['sometimes', 'nullable', 'string',],
                'cover.asset_id' => ['sometimes', 'nullable', 'string', 'exists:assets,id', new AssetTypeRule('image')],
                'price' => ['sometimes', 'nullable'],
                'price.amount' => ['sometimes', 'nullable', 'min:0', 'numeric'],
                'tags' => ['sometimes',],
                'tags.*.id' => ['required', 'string', 'exists:tags,id'],
                'tags.*.action' => ['required', 'string', 'regex:(add|remove)'],
                'is_available' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1'],
            ]);

            if ($validator1->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator1->errors()->toArray());
            }

            //make sure user owns content
            $content = Content::where('id', $id)->where('user_id', $request->user()->id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest("You do not have permission to update this content");
            }

            $validator2 =  Validator::make(array_merge($request->all()), [
                'asset_id' => ['sometimes', 'nullable', 'exists:assets,id', new AssetTypeRule($content->type)],
            ]);
            if ($validator2->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator2->errors()->toArray());
            }

            $user = $request->user();
            if (!is_null($request->title)) {
                $content->title = $request->title;
            }
    
            if (!is_null($request->description)) {
                $content->description = $request->description;
            }
    
            if (!is_null($request->is_available)) {
                $content->is_available = $request->is_available;
            }

            $content->save();

            if (!is_null($request->cover) && array_key_exists('asset_id', $request->cover)) {
                $oldCover = $content->cover()->first();
                $content->cover()->detach($oldCover->id);
                $oldCover->delete();
                $content->cover()->attach($request->cover['asset_id'], [
                    'id' => Str::uuid(),
                    'purpose' => 'cover',
                ]);
            }

            if (!is_null($request->asset_id)) {
                $oldAsset = $content->assets()->first();
                $content->assets()->detach($oldAsset->id);
                $oldAsset->resolutions()->delete();
                $oldAsset->delete();
                $content->assets()->attach($request->asset_id, [
                    'id' => Str::uuid(),
                    'purpose' => 'content-asset',
                ]);
            }

            if (!is_null($request->tags) && is_array($request->tags)) {
                foreach ($request->tags as $tagData) {
                    if ($tagData['action'] === 'add') {
                        $content->tags()
                        ->syncWithoutDetaching([$tagData['id'] => [
                                'id' => Str::uuid(),
                            ]
                        ]);
                    }
    
                    if ($tagData['action'] === 'remove') {
                        $content->tags()->detach($tagData['id']);
                    }
                }
            }
            
            if (!is_null($request->price)) {
                $price = $content->prices()->first();
                if (is_null($price)) {
                    $content->prices()->create([
                        'amount' => $request->price['amount'],
                        'interval' => 'one-off',
                        'interval_amount' => 1,
                    ]);
                } else {
                    $price->amount = $request->price['amount'];
                    $price->save();
                }
            }
            
            $this->setStatusCode(200);
            return $this->respondWithSuccess("Content has been updated successfully", [
                'content' => new ContentResource($content),
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
                    $contents = Content::whereHas('categories', function (Builder $query) use ($categories) {
                        $query->whereIn('name', $categories);
                    })->where('title', 'LIKE', '%' . $title . '%')->where('is_available', 1)->where('show_only_in_collections', 0)->where('approved_by_admin', 1);
                } else {
                    $contents = Content::where('title', 'LIKE', '%' . $title . '%')->where('is_available', 1)->where('show_only_in_collections', 0)->where('approved_by_admin', 1);
                }
    
                if ($freeItems == 1) {
                    $contents = $contents->whereHas('prices', function (Builder $query) {
                        $query->where('amount', 0);
                    });
                }
    
                if ($types != "") {
                    $types = explode(",", urldecode($types));
                    $types = array_diff($types, [""]);//get rid of empty values
                    $contents = $contents->whereIn('type', $types);
                } 
    
                if ($creators != "") {
                    $creators = explode(",", urldecode($creators));
                    $creators = array_diff($creators, [""]);//get rid of empty values
                    $creators_id = User::whereIn('public_id', $creators)->pluck('id')->toArray();
                    $contents = $contents->whereIn('user_id', $creators_id );
                } 
    
                if ($request->user() == NULL || $request->user()->id == NULL) {
                    $user_id = 0;
                } else {
                    $user_id = $request->user()->id;
                }
                $contents = $contents->withCount([
                    'ratings' => function ($query) {
                        $query->where('rating', '>', 0);
                    }
                ])->withAvg([
                    'ratings' => function($query)
                    {
                        $query->where('rating', '>', 0);
                    }
                ], 'rating')->with('cover')->with('owner','owner.profile_picture')->with('categories')->with('prices', 'prices.continent', 'prices.country')->with(['userables' => function ($query) use ($user_id) {
                    $query->with('subscription')->where('user_id',  $user_id)->where('status', 'available');
                }])->orderBy('created_at', 'desc')->paginate($limit, array('*'), 'page', $page);
            
            return $this->respondWithSuccess("Contents retrieved successfully",[
                'contents' => ContentResource::collection($contents),
                'current_page' => $contents->currentPage(),
                'items_per_page' => $contents->perPage(),
                'total' => $contents->total(),
            ]);

        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getUserCreatedContents(Request $request, $user_public_id)
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
                    $contents = $user->contentsCreated()->whereHas('categories', function (Builder $query) use ($categories) {
                        $query->whereIn('name', $categories);
                    })->where('title', 'LIKE', '%' . $title . '%');
                } else {
                    $contents = $user->contentsCreated()->where('title', 'LIKE', '%' . $title . '%');
                }

                if ($freeItems == 1) {
                    $contents = $contents->whereHas('prices', function (Builder $query) {
                        $query->where('amount', 0);
                    });
                }

                if ($request->user() == NULL || $request->user()->id == NULL) {
                    $contents = $contents->where('is_available', 1)->where('show_only_in_collections', 0)->where('approved_by_admin', 1);
                } else {
                    if ($request->user()->id !== $user->id) {
                        $contents = $contents->where('is_available', 1)->where('show_only_in_collections', 0)->where('approved_by_admin', 1);
                    }
                }

                if ($types != "") {
                    $types = explode(",", urldecode($types));
                    $types = array_diff($types, [""]);//get rid of empty values
                    $contents = $contents->whereIn('type', $types);
                } 

                $contents = $contents->withCount([
                    'ratings' => function ($query) {
                        $query->where('rating', '>', 0);
                    }
                ])->withAvg([
                    'ratings' => function($query)
                    {
                        $query->where('rating', '>', 0);
                    }
                ], 'rating')->with('cover')->with('approvalRequest')->with('owner', 'owner.profile_picture')->with('categories')->with('prices', 'prices.continent', 'prices.country')->orderBy('created_at', 'desc')->paginate($limit, array('*'), 'page', $page);
            return $this->respondWithSuccess("Contents retrieved successfully",[
                'contents' => ContentResource::collection($contents),
                'current_page' => $contents->currentPage(),
                'items_per_page' => $contents->perPage(),
                'total' => $contents->total(),
            ]);

        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getReviews(Request $request, $public_id)
    {
        try {
            $content = Content::where('public_id', $public_id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest("Invalid content ID supplied");
            }
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }

                $reviews = $content->reviews()->with('user', 'user.profile_picture', 'user.roles')->orderBy('created_at', 'desc')->paginate($limit, array('*'), 'page', $page);
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
                'public_id' => ['required', 'string', 'exists:contents,public_id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            if ($request->user() == NULL || $request->user()->id == NULL) {
                $user_id = 0;
            } else {
                $user_id = $request->user()->id;
            }
            
            $content = Content::where('public_id', $public_id)->withCount([
                'ratings' => function ($query) {
                    $query->where('rating', '>', 0);
                }
            ])->withAvg([
                'ratings' => function($query)
                {
                    $query->where('rating', '>', 0);
                }
            ], 'rating')->with('cover')->with('approvalRequest')->with('categories','benefactors', 'benefactors.user')->with('prices', 'prices.continent', 'prices.country')->with('owner', 'owner.profile_picture')->with(['userables' => function ($query) use ($user_id) {
                $query->with('subscription')->where('user_id',  $user_id)->where('status', 'available');
            }])->first();
            return $this->respondWithSuccess("Content retrieved successfully",[
                'content' => new ContentResource($content),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getAssets(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(['public_id' => $public_id], [
                'public_id' => ['required', 'string', 'exists:contents,public_id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
           
            $content = Content::where('public_id', $public_id)->first();
            $freePrice = $content->prices()->where('amount', 0)->first();
            //check if user owns this content if it is not free
            $userable = Userable::where('user_id',  $request->user()->id)->where('userable_type', 'content')->where('userable_id', $content->id)->where('status', 'available')->first();
            if (is_null($freePrice) && $content->user_id !== $request->user()->id && !$request->user()->hasRole(Roles::ADMIN) && !$request->user()->hasRole(Roles::SUPER_ADMIN)) {
                if (is_null($userable)) {
                    $this->setStatusCode(402);
                    return $this->respondBadRequest("You do not have permission to view this paid content as you have not purchased it or are not subscribed to it.");
                }
            }
            //only admins or creators can access this via web
            if ($request->user()->id !== $content->user_id && !$request->user()->hasRole(Roles::ADMIN) && !$request->user()->hasRole(Roles::SUPER_ADMIN)  && trim($request->header('User-Agent')) !== 'Dart/2.10 (dart:io)') {
                return $this->respondBadRequest("An error occurred");
            }
            
            return $this->respondWithSuccess("Assets retrieved successfully",[
                'assets' => $content->assets()->with('resolutions')->where('purpose', '<>', 'cover')->get(),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function getFreeAssets(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(['public_id' => $public_id], [
                'public_id' => ['required', 'string', 'exists:contents,public_id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
           
            $content = Content::where('public_id', $public_id)->first();
            $freePrice = $content->prices()->where('amount', 0)->first();
            if (is_null($freePrice)) {
                $this->setStatusCode(402);
                return $this->respondBadRequest("You do not have permission to view this paid content as you have not purchased it or are not subscribed to it.");
            }
            //only admins or creators can access this via web
            if ($request->user()->id !== $content->user_id && !$request->user()->hasRole(Roles::ADMIN) && !$request->user()->hasRole(Roles::SUPER_ADMIN)  && trim($request->header('User-Agent')) !== 'Dart/2.10 (dart:io)') {
                return $this->respondBadRequest("An error occurred");
            }
            
            return $this->respondWithSuccess("Assets retrieved successfully",[
                'assets' => $content->assets()->where('purpose', '<>', 'cover')->get(),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }
}
