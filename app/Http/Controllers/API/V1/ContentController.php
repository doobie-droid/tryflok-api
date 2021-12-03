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
use App\Models\ContentIssue;
use App\Models\Price;
use App\Models\Review;
use App\Models\Userable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use App\Rules\AssetType as AssetTypeRule;
use App\Http\Resources\ContentResource;
use App\Http\Resources\ContentIssueResource;
use App\Jobs\Content\DispatchSubscribersNotification as DispatchSubscribersNotificationJob;
use App\Jobs\Content\DispatchNotificationToFollowers as DispatchNotificationToFollowersJob;
use App\Jobs\Content\DispatchDisableLiveUserable as DispatchDisableLiveUserableJob;
use App\Services\LiveStream\Agora\RtcTokenBuilder as AgoraRtcToken;
use Aws\CloudFront\CloudFrontClient;
use Aws\Exception\AwsException;

class ContentController extends Controller
{
    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => ['required', 'string', 'max: 200'],
                'description' => ['sometimes', 'string'],
                'digiverse_id' => ['required','exists:collections,id'],
                'cover.asset_id' => ['required_if:type,pdf,audio,video,newsletter', 'string', 'exists:assets,id', new AssetTypeRule('image')],
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

            if ($digiverse->user_id !== $request->user()->id) {
                return $this->respondBadRequest("You cannot to this digiverse because you do not own it");
            }

            if ($request->type === 'newsletter') {
                $digiverseNewsletterCount = $digiverse->contents()->where('type', 'newsletter')->count();
                if ($digiverseNewsletterCount > 0) {
                    return $this->respondBadRequest("This Digiverse already has a newsletter. Only one newsletter allowed per digiverse.");
                }
            }
            $user = $request->user();
            /*if ($request->type === 'live-audio') {
                $live_audio_count = Content::where('type', 'live-audio')->count();
                if ($live_audio_count > 0) {
                    return $this->respondBadRequest("You can only create one live audio");
                }
            }
            if ($request->type ===  'live-video') {
                $live_video_count = Content::where('type', 'live-video')->count();
                if ($live_video_count > 0) {
                    return $this->respondBadRequest("You can only create one live video");
                }
            }*/

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

            if ($content->type === 'live-audio' || $content->type === 'live-video') {
                $content->metas()->createMany([
                    [
                        'key' => 'live_status',
                        'value' => 'inactive',
                    ],
                    [
                        'key' => 'channel_name',
                        'value' => "{$content->id}-" . date('Ymd'),
                    ],
                    [
                        'key' => 'live_token',
                        'value' => '',
                    ],
                    [
                        'key' => 'join_count',
                        'value' => 0,
                    ]
                ]);
            }

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

            if (!is_null($request->cover) && array_key_exists('asset_id', $request->cover)) {
                $content->cover()->attach($request->cover['asset_id'], [
                    'id' => Str::uuid(),
                    'purpose' => 'cover',
                ]);
            }
            
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

            $content = Content::where('id', $content->id)
            ->with('prices', 'cover', 'owner', 'tags')
            ->withCount('subscribers')
            ->with('metas')
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
            ->first();

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
            $content = Content::where('id', $id)->where('user_id', $request->user()->id)
            ->with('prices', 'cover', 'owner', 'tags')
            ->withCount('subscribers')
            ->with('metas')
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
            ->first();
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

            return $this->respondWithSuccess('Content has been updated successfully', [
                'content' => new ContentResource($content),
            ]);

        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function createIssue(Request $request, $id) {
        try {
            $validator1 = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:contents,id',],
                'title' => ['required', 'string', 'max:200', 'min:1',],
                'description' => ['required', 'string',],
            ]);

            if ($validator1->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator1->errors()->toArray());
            }

            $content = Content::where('id', $id)->where('user_id', $request->user()->id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest("You do not have permission to create an issue for this content");
            }

            if ($content->type !== 'newsletter') {
                return $this->respondBadRequest("Issues can only be created for newletters");
            }
            
            $issue = $content->issues()->create([
                'title' => $request->title,
                'description' => $request->description,
                'is_available' => 0,
            ]);

            return $this->respondWithSuccess('Issue has been created successfully', [
                'issue' => $issue,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function updateIssue(Request $request, $id) {
        try {
            $validator1 = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:contents,id',],
                'issue_id' => ['required', 'string', 'exists:content_issues,id',],
                'title' => ['sometimes', 'nullable', 'string', 'max:200', 'min:1',],
                'description' => ['sometimes', 'nullable', 'string',],
            ]);

            if ($validator1->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator1->errors()->toArray());
            }

            $content = Content::where('id', $id)->where('user_id', $request->user()->id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest("You do not have permission to create an issue for this content");
            }

            if ($content->type !== 'newsletter') {
                return $this->respondBadRequest("Issues can only be created or updated for newletters");
            }
            
            $issue = $content->issues()->where('id', $request->issue_id)->first();

            if (!is_null($request->title)) {
                $issue->title = $request->title;
            }
    
            if (!is_null($request->description)) {
                $issue->description = $request->description;
            }

            $issue->save();

            return $this->respondWithSuccess('Issue has been updated successfully', [
                'issue' => $issue,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function publishIssue(Request $request, $id) {
        try {
            $validator1 = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:contents,id',],
                'issue_id' => ['required', 'string', 'exists:content_issues,id',],
            ]);

            if ($validator1->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator1->errors()->toArray());
            }

            $content = Content::where('id', $id)->where('user_id', $request->user()->id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest("You do not have permission to create an issue for this content");
            }

            if ($content->type !== 'newsletter') {
                return $this->respondBadRequest("Issues can only be created or updated for newletters");
            }
            
            $issue = $content->issues()->where('id', $request->issue_id)->first();

            if ($issue->is_available !== 1) {
                $issue->is_available = 1;
                $issue->save();
                DispatchSubscribersNotificationJob::dispatch([
                    'content' => $content,
                ]);
            }
            
            return $this->respondWithSuccess('Issue has been published successfully', [
                'issue' => $issue,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function getSingle(Request $request, $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'string', 'exists:contents,id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            if ($request->user() == NULL || $request->user()->id == NULL) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }
            
            $content = Content::where('id', $id)
            ->with('prices', 'cover', 'owner', 'tags')
            ->withCount('subscribers')
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
            ->with([
                'userables' => function ($query) use ($user_id) {
                    $query->with('subscription')->where('user_id',  $user_id)->where('status', 'available');
                },
            ])
            ->with([
                'subscribers' => function ($query) use ($user_id) {
                    $query->where('users.id',  $user_id);
                },
            ])
            ->with('issues')
            ->with('metas')
            ->first();
            return $this->respondWithSuccess('Content retrieved successfully',[
                'content' => new ContentResource($content),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		} 
    }

    public function getDigiverseContents(Request $request, $digiverse_id)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);

            $keyword = urldecode($request->query('keyword', ''));
            $keywords = explode(" ", $keyword);
            $keywords = array_diff($keywords, ['']);

            $types = $request->query('types', '');
            $types = explode(",", urldecode($types));
            $types = array_diff($types, [""]);

            $tags = $request->query('tags', '');
            $tags = explode(",", urldecode($tags));
            $tags = array_diff($tags, ['']);

            $creators = $request->query('creators', '');
            $creators = explode(",", urldecode($creators));
            $creators = array_diff($creators, ['']);

            $maxPrice = $request->query('max_price', -1);
            $minPrice = $request->query('min_price', 0);

            $orderBy = $request->query('order_by', 'created_at');
            $orderDirection = $request->query('order_direction', 'asc');

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
                'page' => ['required', 'integer', 'min:1',],
                'limit' => ['required', 'integer', 'min:1', "max:{$max_items_count}",],
                'keyword' => ['sometimes', 'string', 'max:200',],
                'max_price' => ['required', 'integer', 'min:-1',],
                'min_price' => ['required', 'integer', 'min:0',],
                'order_by' => ['required', 'string', 'regex:(created_at|price|views|reviews)'],
                'order_direction' => ['required', 'string', 'regex:(asc|desc)'],
                'types' => ['sometimes',],
                'type.*' => ['required', 'string',],
                'tags' => ['sometimes',],
                'tags.*' => ['required', 'string', 'exists:tags,id',],
                'creators' => ['sometimes',],
                'creators.*' => ['required', 'string', 'exists:users,id',],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
            $digiverse = Collection::where('id', $request->digiverse_id)->first();
            $contents = $digiverse->contents();

            if ($request->user() == NULL || $request->user()->id == NULL) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            if ($user_id !== $digiverse->user_id) {
                $contents = $contents->where('is_available', 1);
            }

            foreach ($keywords as $keyword) {
                $contents = $contents->where(function ($query) use ($keyword) {
                    $query->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('description', 'LIKE', "%{$keyword}%");
                });
            }

            if (!empty($types)) {
                $contents = $contents->whereIn('type', $types );
            }

            if (!empty($tags)) {
                $contents = $contents->whereHas('tags', function (Builder $query) use ($tags) {
                    $query->whereIn('tags.id', $tags);
                });
            }

            if (!empty($creators)) {
                $contents = $contents->whereIn('user_id', $creators );
            }

            if ($request->user() == NULL || $request->user()->id == NULL) {
                $user_id = 0;
            } else {
                $user_id = $request->user()->id;
            }

            $contents = $contents
            ->withCount('subscribers')
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
            ->with('cover')
            ->with('owner','owner.profile_picture')
            ->with('tags')
            ->with('prices')
            ->with([
                'userables' => function ($query) use ($user_id) {
                    $query->with('subscription')->where('user_id',  $user_id)->where('status', 'available');
                },
            ])->orderBy('contents.created_at', 'desc')
            ->paginate($limit, array('*'), 'page', $page);

            return $this->respondWithSuccess('Contents retrieved successfully',[
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

    public function getIssues(Request $request, $id)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);

            $keyword = urldecode($request->query('keyword', ''));
            $keywords = explode(" ", $keyword);
            $keywords = array_diff($keywords, ['']);

            $max_items_count = Constants::MAX_ITEMS_LIMIT;

            $validator = Validator::make([
                'id' => $id,
                'page' => $page,
                'limit' => $limit,
                'keyword' => $keyword,
            ], [
                'id' => ['required', 'string', 'exists:contents,id'],
                'page' => ['required', 'integer', 'min:1',],
                'limit' => ['required', 'integer', 'min:1', "max:{$max_items_count}",],
                'keyword' => ['sometimes', 'string', 'max:200',],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
            $content = Content::where('id', $id)->first();
            $issues = $content->issues();
            if ($request->user()->id !== $content->user_id) {
                $issues = $issues->where('is_available', 1);
            }

            foreach ($keywords as $keyword) {
                $issues = $issues->where(function ($query) use ($keyword) {
                    $query->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('description', 'LIKE', "%{$keyword}%");
                });
            }

            $issues = $issues->orderBy('content_issues.created_at', 'desc')
            ->paginate($limit, array('*'), 'page', $page);
            return $this->respondWithSuccess('Issues retrieved successfully',[
                'issues' => ContentIssueResource::collection($issues),
                'current_page' => $issues->currentPage(),
                'items_per_page' => $issues->perPage(),
                'total' => $issues->total(),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function getSingleIssue(Request $request, $id)
    {
        try {
            $validator = Validator::make([
                'id' => $id,
            ], [
                'id' => ['required', 'string', 'exists:issues,id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $issue = ContentIssue::where('id', $id)->first();
            return $this->respondWithSuccess('Issue retrieved successfully',[
                'issue' => new ContentIssueResource($issue),
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function subscribeToContent(Request $request, $id)
    {
        try {
            $validator = Validator::make([
                'id' => $id,
            ], [
                'id' => ['required', 'string', 'exists:contents,id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();
            $content->subscribers()->syncWithoutDetaching([$request->user()->id => [
                    'id' => Str::uuid(),
                ]
            ]);
            return $this->respondWithSuccess('You have been successfully subscribed');
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function unsubscribeFromContent(Request $request, $id)
    {
        try {
            $validator = Validator::make([
                'id' => $id,
            ], [
                'id' => ['required', 'string', 'exists:contents,id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();
            $content->subscribers()->detach([$request->user()->id]);
            return $this->respondWithSuccess('You have successfully unsubscribed');
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function startLive(Request $request, $id)
    {
        try {
            $validator = Validator::make([
                'id' => $id,
            ], [
                'id' => ['required', 'string', 'exists:contents,id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();
            if ($content->type !== 'live-video' && $content->type !== 'live-audio') {
                return $this->respondBadRequest("Live broadcasts can only be started for live content types");
            }

            if ($content->user_id !== $request->user()->id) {
                return $this->respondBadRequest("Only the creator can start the live broadcast");
            }

            $channel = $content->metas()->where('key', 'channel_name')->first();
            $status =  $content->metas()->where('key', 'live_status')->first();
            $token = $content->metas()->where('key', 'live_token')->first();
            $join_count = $content->metas()->where('key', 'join_count')->first();
            //ensure that the live has not been started before
            if ($status->value === "active") {
                return $this->respondWithSuccess('Channel started successfully', [
                    'token' => $token->value,
                    'channel_name' => $channel->value,
                    'uid' => 0,
                ]);
            }

            $expires = time() + (24 * 60 * 60); // let token last for 24hrs
            $agora_token = AgoraRtcToken::buildTokenWithUid(env('AGORA_APP_ID'), env('AGORA_APP_CERTIFICATE'), $channel->value, 0, AgoraRtcToken::ROLE_PUBLISHER, $expires);

            $token->value = $agora_token;
            $token->save();
            
            $join_count->value = 1;
            $join_count->save();

            $status->value = 'active';
            $status->save();
            
            DispatchNotificationToFollowersJob::dispatch([
                'notificable_id' => $content->id,
                'notificable_type' => 'content',
                'user' => $request->user(),
                'message' => "{$request->user()->username} has started a new live",
            ]);

            return $this->respondWithSuccess('Channel started successfully', [
                'token' => $token->value,
                'channel_name' => $channel->value,
                'uid' => 0,
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function joinLive(Request $request, $id)
    {
        try {
            $validator = Validator::make([
                'id' => $id,
            ], [
                'id' => ['required', 'string', 'exists:contents,id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();
            if ($content->type !== 'live-video' && $content->type !== 'live-audio') {
                return $this->respondBadRequest("Live broadcasts can only be joined for live content types");
            }

            if ($request->user() == NULL || $request->user()->id == NULL) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            if (!$content->isFree() && !$content->userHasPaid($user_id) && !($content->user_id == $user_id)) {
                return $this->respondBadRequest("You do not have access to this live because you have not purchased it");
            }

            $status =  $content->metas()->where('key', 'live_status')->first();
            if ($status->value !== 'active') {
                return $this->respondBadRequest("You cannot join a broadcast that has been not started");
            }

            $content->subscribers()->syncWithoutDetaching([$request->user()->id => [
                    'id' => Str::uuid(),
                ]
            ]);

            $channel = $content->metas()->where('key', 'channel_name')->first();
            $token = $content->metas()->where('key', 'live_token')->first();
            $join_count = $content->metas()->where('key', 'join_count')->first();
            $uid = $join_count->value;
            $join_count->value = (int)$join_count->value + 1;
            $join_count->save();
            $expires = time() + (24 * 60 * 60); // let token last for 24hrs
           // $token = AgoraRtcToken::buildTokenWithUid(env('AGORA_APP_ID'), env('AGORA_APP_CERTIFICATE'), $channel->value, $uid, AgoraRtcToken::ROLE_ATTENDEE, $expires);

            return $this->respondWithSuccess('Channel joined successfully', [
                'token' => $token->value,
                'channel_name' => $channel->value,
                'uid' => (int)$uid,
            ]);

        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function leaveLive(Request $request, $id)
    {
        try {
            $validator = Validator::make([
                'id' => $id,
            ], [
                'id' => ['required', 'string', 'exists:contents,id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();
            if ($content->type !== 'live-video' && $content->type !== 'live-audio') {
                return $this->respondBadRequest("Live broadcasts can only be left for live content types");
            }
            $content->subscribers()->detach([$request->user()->id]);

            return $this->respondWithSuccess('Channel left successfully');
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function endLive(Request $request, $id)
    {
        try {
            $validator = Validator::make([
                'id' => $id,
            ], [
                'id' => ['required', 'string', 'exists:contents,id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();
            if ($content->type !== 'live-video' && $content->type !== 'live-audio') {
                return $this->respondBadRequest("Live broadcasts can only be ended for live content types");
            }

            if ($content->user_id !== $request->user()->id) {
                return $this->respondBadRequest("Only the creator can start the live broadcast");
            }

            $join_count = $content->metas()->where('key', 'join_count')->first();
            $join_count->value = 0;
            $join_count->save();
            $status =  $content->metas()->where('key', 'live_status')->first();
            
            $status->value = 'inactive';
            $status->save();
            
            DispatchDisableLiveUserableJob::dispatch([
                'live_content' => $content,
            ]);
            return $this->respondWithSuccess('Channel ended successfully');
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function getReviews(Request $request, $id)
    {
        try {
            $content = Content::where('id', $id)->first();
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

    public function getAssets(Request $request, $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'string', 'exists:contents,id'],
            ]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
           
            if ($request->user() == NULL || $request->user()->id == NULL) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            $content = Content::where('id', $id)->first();
            
            if (!$content->isFree() && !$content->userHasPaid($user_id) && !($content->user_id == $user_id)) {
                return $this->respondBadRequest("You are not permitted to view the assets of this content");
            }
            // get signed cookies
            $cloudFrontClient = new CloudFrontClient([
                'profile' => 'default',
                'version' => '2014-11-06',
                'region' => 'us-east-1'
            ]);

            $expires = time() + (2 * 60 * 60); //2 hours from now(in seconds)
            $resource = env('PRIVATE_AWS_CLOUDFRONT_URL') . '/*';
            $policy = <<<POLICY
                        {
                            "Statement": [
                                {
                                    "Resource": "{$resource}",
                                    "Condition": {
                                        "DateLessThan": {"AWS:EpochTime": {$expires}}
                                    }
                                }
                            ]
                        }
                        POLICY;
            $result = $cloudFrontClient->getSignedCookie([
                'policy' => $policy,
                'private_key' => base64_decode(env('AWS_CLOUDFRONT_PRIVATE_KEY')),
                'key_pair_id' => env('AWS_CLOUDFRONT_KEY_ID'),
            ]);
            $cookies = '';
            foreach ($result as $key => $value) {
                $cookies = $cookies . $key . '=' . $value . ';';
            }
            return $this->respondWithSuccess("Assets retrieved successfully",[
                'assets' => $content->assets()->with('resolutions')->wherePivot('purpose', 'content-asset')->get(),
                'cookies' => $cookies,
                'cookies_expire' => $expires
            ]);
        } catch(\Exception $exception) {
            Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }
}
