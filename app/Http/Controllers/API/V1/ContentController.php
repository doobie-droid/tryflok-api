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
                'title' => ['required', 'string'],
                'description' => ['sometimes', 'string'],
                'digiverse_id' => ['required','exists:collections,id'],
                'cover.asset_id' => ['required', 'string', 'exists:assets,id', new AssetTypeRule('image')],
                'price' => ['required',],
                'price.amount' => ['required', 'min:0', 'numeric'],
                'price.interval' => ['required', 'string', 'regex:(one-off|monthly)'],
                'price.interval_amount' => ['required','min:1', 'max:1', 'numeric', 'integer'],
                'tags' => ['sometimes',],
                'tags.*' => ['required', 'string', 'exists:tags,id'],
                'type' => ['required', 'string', 'regex:(pdf|audio|video|newsletter|live-audio|live-video)'],
                'asset_id' => ['required_if:type,pdf,audio,video', 'nullable', 'exists:assets,id', new AssetTypeRule($request->type)]
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
                'is_available' => 1,
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
                'interval' => $request->price['interval'],
                'interval_amount' => $request->price['interval_amount'],
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

    public function update(Request $request, $public_id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $public_id]), [
                'id' => ['required', 'string', 'exists:contents,public_id'],
                'title' => ['sometimes', 'nullable', 'string'],
                'summary' => ['sometimes','nullable', 'string'],
                'price' => ['sometimes','nullable', 'numeric', 'min:0', 'max:9999.99'],
                'cover' => ['sometimes','nullable', 'image', 'max:1024'],//1MB
                'type' => ['required_with:zip,audio,video', 'string', 'regex:(book|audio|video)'],
                'audio' => ['sometimes', 'nullable', 'file', 'max:102400'],//100MB  - 1 hour of max audio quality
                'video' => ['sometimes', 'nullable', 'file', 'max:4096000'],//4GB
                'format' => ['required_with:zip', 'string', 'regex:(pdf|2d-image|3d-image)'],
                'zip' => ['sometimes', 'nullable', 'mimes:zip', 'max:102400'],//100MB - each page must not surpass 1MB and max of 100 pages
                'categories' => ['sometimes', 'nullable'],
                'categories.*.action' => ['required', 'string', 'regex:(add|remove)'],
                'categories.*.public_id' => ['required', 'string','exists:categories,public_id'],
                'benefactors' => ['sometimes', 'nullable'],
                'benefactors.*.action' => ['required', 'string', 'regex:(add|remove|update)'],
                'benefactors.*.public_id' => ['required', 'exists:users,public_id'],
                'is_available' => ['sometimes', 'nullable', 'integer', 'regex:(0|1)'],
                'show_only_in_collections' => ['sometimes', 'nullable', 'integer', 'regex:(0|1)'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            //make sure user owns content
            $content = Content::where('public_id', $public_id)->where('user_id', $request->user()->id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest("You do not have permission to update this content");
            }

            //validate benefactors
            $currentBenefactors = $content->benefactors()->get();
            $benefactorsToAdd = [];
            $benefactorsToUpdate = [];
            $benefactorsToDelete = [];
            if (isset($request->benefactors) && is_array($request->benefactors)) {
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
            }

            $user = $request->user();

            $english = Cache::remember('languages:english', Constants::MONTH_CACHE_TIME, function () {
                return Language::where('name', 'english')->first();
            });

            $coverPath = "";
            if ($request->hasFile('cover'))
            {
                $path = Storage::disk('local')->put('uploads/covers', $request->cover);
                $coverPath = storage_path() . "/app/" . $path;
            } 
            $uploadedFilePath = "";
            switch ($request->type) {
                case "book":
                    if ($request->hasFile('zip'))
                    {
                        $path = Storage::disk('local')->put('uploads/zips', $request->zip);
                        $uploadedFilePath = storage_path() . "/app/" . $path;
                    } 
                    break;
                case "audio":
                    if ($request->hasFile('audio'))
                    {
                        $path = Storage::disk('local')->put('uploads/audios', $request->audio);
                        $uploadedFilePath = storage_path() . "/app/" . $path;
                    } 
                    break;
                case "video":
                    if ($request->hasFile('video'))
                    {
                        $path = Storage::disk('local')->put('uploads/videos', $request->video);
                        $uploadedFilePath = storage_path() . "/app/" . $path;
                    } 
                    break;
            }

            EditContentJob::dispatch([
                'content' => $content,
                'title' => isset($request->title) ? $request->title : NULL,
                'summary' => isset($request->summary) ? $request->summary : NULL,
                'price' => isset($request->price) ? $request->price : NULL,
                'type' => isset($request->type) ? $request->type : NULL,
                'format' => isset($request->format) ? $request->format : NULL,
                'cover_path' => $coverPath,
                'uploaded_file_path' => $uploadedFilePath,
                'language' => $english,
                'owner' => $user,
                'categories' => isset($request->categories) ? $request->categories : NULL,
                'benefactors_to_add' => $benefactorsToAdd,
                'benefactors_to_update' => $benefactorsToUpdate,
                'benefactors_to_delete' => $benefactorsToDelete,
                'is_available' => isset($request->is_available) ? $request->is_available : NULL,
                'show_only_in_collections' => isset($request->show_only_in_collections) ? $request->show_only_in_collections : NULL,
            ]);
            
            $this->setStatusCode(202);
            return $this->respondWithSuccess("Content has been queued for update. It would be uploaded shortly");

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
