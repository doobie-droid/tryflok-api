<?php

namespace App\Http\Controllers\API\V1;

use App\Constants\Constants;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContentIssueResource;
use App\Http\Resources\ContentResource;
use App\Jobs\Assets\UploadResource\Html as UploadHtmlJob;
use App\Jobs\Content\DispatchDisableLiveUserable as DispatchDisableLiveUserableJob;
use App\Jobs\Content\DispatchNotificationToFollowers as DispatchNotificationToFollowersJob;
use App\Jobs\Content\DispatchSubscribersNotification as DispatchSubscribersNotificationJob;
use App\Jobs\Users\NotifyAddedToChallenge as NotifyAddedToChallengeJob;
use App\Jobs\Users\NotifyChallengeResponse as NotifyChallengeResponseJob;
use App\Models\Asset;
use App\Models\Collection;
use App\Models\Content;
use App\Models\ContentIssue;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Rules\AssetType as AssetTypeRule;
use App\Rules\SumCheck as SumCheckRule;
use App\Services\LiveStream\Agora\RtcTokenBuilder as AgoraRtcToken;
use App\Services\LiveStream\Agora\RtmTokenBuilder as AgoraRtmToken;
use Aws\CloudFront\CloudFrontClient;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => ['required', 'string', 'max: 200'],
                'description' => ['required', 'string'],
                'digiverse_id' => ['required','exists:collections,id'],
                'cover.asset_id' => ['required_if:type,pdf,audio,video,newsletter', 'string', 'exists:assets,id', new AssetTypeRule('image')],
                'price' => ['required'],
                'price.amount' => ['required', 'min:0', 'numeric', 'max:1000'],
                'tags' => ['sometimes'],
                'tags.*' => ['required', 'string', 'exists:tags,id'],
                'type' => ['required', 'string', 'in:pdf,audio,video,newsletter,live-audio,live-video'],
                'asset_id' => ['required_if:type,pdf,audio,video', 'nullable', 'exists:assets,id', new AssetTypeRule($request->type)],
                'scheduled_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:now'],
                'article' => ['required_if:type,newsletter', 'string'],
                'is_challenge' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1'],
                'pot_size' => ['required_if:is_challenge,1', 'integer', 'min:0'],
                'minimum_contribution' => ['required_if:is_challenge,1', 'integer', 'min:10'],
                'moderator_share' => ['required_if:is_challenge,1', 'integer', 'max:10', 'min:0'],
                'loser_share' => ['required_if:is_challenge,1', 'integer', 'max:50', 'min:0'],
                'winner_share' => ['required_if:is_challenge,1', 'integer', 'max:100', 'min:45', 'gte:loser_share', new SumCheckRule(['moderator_share', 'loser_share'], 100)],
                'contestants' => ['required_if:is_challenge,1', 'size:2'],
                'contestants.*' => ['required_if:is_challenge,1', 'string', 'distinct', 'exists:users,id', "not_in:{$request->user()->id}"],

            ], [
                'contestants.*.not_in' => 'You cannot make yourself a contestant',
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            if (! in_array($request->type, ['live-video']) && isset($request->is_challenge) && (int) $request->is_challenge === 1) {
                return $this->respondBadRequest("Only live video content can be a challenge");
            }

            $digiverse = Collection::where('id', $request->digiverse_id)->where('type', 'digiverse')->first();
            if (is_null($digiverse)) {
                return $this->respondBadRequest("The collection with ID {$request->digiverse_id} is not a digiverse");
            }

            if ($digiverse->user_id !== $request->user()->id) {
                return $this->respondBadRequest('You cannot to this digiverse because you do not own it');
            }

            $user = $request->user();
            $is_available = 0;
            $is_challenge = 0;

            if (in_array($request->type, ['live-audio', 'live-video', 'pdf', 'audio'])) {
                $is_available = 1;
            }

            if (isset($request->is_challenge) && (int) $request->is_challenge === 1) {
                $is_challenge = 1;
            }

            $content = Content::create([
                'title' => $request->title,
                'description' => $request->description,
                'user_id' => $user->id,
                'type' => $request->type,
                'is_available' => $is_available,
                'approved_by_admin' => 1,
                'show_only_in_digiverses' => 1,
                'live_status' => 'inactive',
                'is_challenge' => $is_challenge,
            ]);

            if (! is_null($request->scheduled_date)) {
                $content->scheduled_date = $request->scheduled_date;
                $content->save();
            }

            if ($content->type === 'live-audio' || $content->type === 'live-video') {
                $content->metas()->createMany([
                    [
                        'key' => 'channel_name',
                        'value' => "{$content->id}-" . date('Ymd'),
                    ],
                    [
                        'key' => 'rtc_token',
                        'value' => '',
                    ],
                    [
                        'key' => 'rtm_token',
                        'value' => '',
                    ],
                    [
                        'key' => 'join_count',
                        'value' => 0,
                    ],
                ]);

                $content->liveHosts()->create([
                    'user_id' => $user->id,
                    'designation' => 'host',
                ]);
                $content->liveBroadcasters()->create([
                    'user_id' => $user->id,
                ]);
            }

            if ($is_challenge === 1) {
                $content->metas()->createMany([
                    [
                        'key' => 'pot_size',
                        'value' => $request->pot_size,
                    ],
                    [
                        'key' => 'minimum_contribution',
                        'value' => $request->minimum_contribution,
                    ],
                    [
                        'key' => 'moderator_share',
                        'value' => $request->moderator_share,
                    ],
                    [
                        'key' => 'winner_share',
                        'value' => $request->winner_share,
                    ],
                    [
                        'key' => 'loser_share',
                        'value' => $request->loser_share,
                    ],
                ]);
                
                foreach ($request->contestants as $contestant_id) {
                    $content->challengeContestants()->create([
                        'user_id' => $contestant_id,
                        'status' => 'pending',
                    ]);
                    $content->liveBroadcasters()->create([
                        'user_id' => $contestant_id,
                    ]);
                    NotifyAddedToChallengeJob::dispatch(User::where('id', $contestant_id)->first(), $content);
                }
            }

            if ($request->type === 'newsletter') {
                $filename = date('Ymd') . Str::random(16);
                $folder = join_path('assets', Str::random(16) . date('Ymd'), 'text');
                $fullFilename = join_path($folder, $filename . '.html');
                $url = join_path(config('flok.public_media_url'), $fullFilename);
                $asset = Asset::create([
                    'url' => $url,
                    'storage_provider' => 'public-s3',
                    'storage_provider_id' => $fullFilename,
                    'asset_type' => 'text',
                    'mime_type' => 'text/html',
                ]);
                $content->assets()->attach($asset->id, [
                    'id' => Str::uuid(),
                    'purpose' => 'content-asset',
                ]);
                
                UploadHtmlJob::dispatch([
                    'asset' => $asset,
                    'article' => $request->article,
                    'full_file_name' => $fullFilename,
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

            if (! is_null($request->cover) && array_key_exists('asset_id', $request->cover)) {
                $content->cover()->attach($request->cover['asset_id'], [
                    'id' => Str::uuid(),
                    'purpose' => 'cover',
                ]);
            }

            if (! is_null($request->asset_id)) {
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
            ->eagerLoadBaseRelations()
            ->eagerLoadSingleContentRelations()
            ->first();

            return $this->respondWithSuccess('Content has been created successfully', [
                'content' => new ContentResource($content),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator1 = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:contents,id'],
                'title' => ['sometimes', 'nullable', 'string', 'max:200', 'min:1'],
                'description' => ['sometimes', 'nullable', 'string'],
                'cover.asset_id' => ['sometimes', 'nullable', 'string', 'exists:assets,id', new AssetTypeRule('image')],
                'price' => ['sometimes', 'nullable'],
                'price.amount' => ['sometimes', 'nullable', 'min:0', 'numeric', 'max:1000'],
                'tags' => ['sometimes'],
                'tags.*.id' => ['required', 'string', 'exists:tags,id'],
                'tags.*.action' => ['required', 'string', 'in:add,remove'],
                'is_available' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1'],
                'scheduled_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:now'],
                'article' => ['sometimes', 'nullable', 'string'],
            ]);

            if ($validator1->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator1->errors()->toArray());
            }

            //make sure user owns content
            $content = Content::where('id', $id)->where('user_id', $request->user()->id)
            ->eagerLoadBaseRelations()
            ->first();
            if (is_null($content)) {
                return $this->respondBadRequest('You do not have permission to update this content');
            }

            $validator2 = Validator::make(array_merge($request->all()), [
                'asset_id' => ['sometimes', 'nullable', 'exists:assets,id', new AssetTypeRule($content->type)],
            ]);
            if ($validator2->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator2->errors()->toArray());
            }

            if (! is_null($request->is_available)) {
                // ensure that asset content is ready before it can be marked as available
                if (in_array($content->type, ['video', 'pdf', 'audio', 'newsletter']) && $content->assets()->first()->processing_complete != 1) {
                    return $this->respondBadRequest("Hey there! We are making some optimizations to your content, please wait till you get a notification that it is ready then you can mark it as available.");
                }
                $content->is_available = $request->is_available;
            }

            $user = $request->user();
            if (! is_null($request->title)) {
                $content->title = $request->title;
            }

            if (! is_null($request->description)) {
                $content->description = $request->description;
            }

            if (! is_null($request->scheduled_date)) {
                $content->scheduled_date = $request->scheduled_date;
            }

            $content->save();

            if (! is_null($request->cover) && array_key_exists('asset_id', $request->cover) && ! is_null($request->cover['asset_id']) && $request->cover['asset_id'] != '') {
                $oldCover = $content->cover()->first();
                if (! is_null($oldCover)) {
                    $content->cover()->detach($oldCover->id);
                    $oldCover->delete();
                }
                
                $content->cover()->attach($request->cover['asset_id'], [
                    'id' => Str::uuid(),
                    'purpose' => 'cover',
                ]);
            }

            if (! is_null($request->asset_id)) {
                $oldAsset = $content->assets()->first();
                $content->assets()->detach($oldAsset->id);
                $oldAsset->resolutions()->delete();
                $oldAsset->delete();
                $content->assets()->attach($request->asset_id, [
                    'id' => Str::uuid(),
                    'purpose' => 'content-asset',
                ]);
            }

            if (! is_null($request->tags) && is_array($request->tags)) {
                foreach ($request->tags as $tagData) {
                    if ($tagData['action'] === 'add') {
                        $content->tags()
                        ->syncWithoutDetaching([
                            $tagData['id'] => [
                                'id' => Str::uuid(),
                            ],
                        ]);
                    }

                    if ($tagData['action'] === 'remove') {
                        $content->tags()->detach($tagData['id']);
                    }
                }
            }

            if (! is_null($request->price)) {
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

            if (! is_null($request->article) && $content->type === 'newsletter') {
                $filename = date('Ymd') . Str::random(16);
                $folder = join_path('assets', Str::random(16) . date('Ymd'), 'text');
                $fullFilename = join_path($folder, $filename . '.html');
                $url = join_path(config('flok.public_media_url'), $fullFilename);

                $oldArticle = $content->assets()->first();
                $oldArticle->url = $url;
                $oldArticle->storage_provider_id = $fullFilename;
                $oldArticle->save();
                UploadHtmlJob::dispatch([
                    'asset' => $oldArticle,
                    'article' => $request->article,
                    'full_file_name' => $fullFilename,
                ]);
            }

            return $this->respondWithSuccess('Content has been updated successfully', [
                'content' => new ContentResource($content),
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
            $content = Content::where('id', $id)->where('user_id', $request->user()->id)
            ->eagerLoadBaseRelations()
            ->first();

            if (is_null($content)) {
                return $this->respondBadRequest('You do not have permission to update this content');
            }

            $content->archived_at = now();
            $content->saved();
            return $this->respondWithSuccess('Content has been archived successfully', [
                'content' => new ContentResource($content),
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
            $content = Content::where('id', $id)->where('user_id', $request->user()->id)
            ->eagerLoadBaseRelations()
            ->first();
            if (is_null($content)) {
                return $this->respondBadRequest('You do not have permission to update this content');
            }

            // make sure there are no active purchases
            $active_purchases = $content->userables()->where('status', 'available')->count();
            if ($active_purchases > 0) {
                return $this->respondBadRequest('You cannot delete a content that has active purchases');
            }

            $content->delete();
            return $this->respondWithSuccess('Content deleted successfully', [
                'content' => new ContentResource($content),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function attachMediaToContent(Request $request, $id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:contents,id'],
                'asset_ids' => ['required'],
                'asset_ids.*' => ['sometimes', 'nullable', 'string', 'exists:assets,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();

            if ($request->user()->id !== $content->user_id) {
                return $this->respondBadRequest('You cannot edit this content because you do not own it');
            }

            foreach ($request->asset_ids as $asset_id) {
                $content->assets()->attach($asset_id, [
                    'id' => Str::uuid(),
                    'purpose' => 'attached-media',
                ]);
            }
            
            return $this->respondWithSuccess('Assets attached successfully');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function createIssue(Request $request, $id)
    {
        try {
            $validator1 = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:contents,id'],
                'title' => ['required', 'string', 'max:200', 'min:1'],
                'description' => ['required', 'string'],
            ]);

            if ($validator1->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator1->errors()->toArray());
            }

            $content = Content::where('id', $id)->where('user_id', $request->user()->id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest('You do not have permission to create an issue for this content');
            }

            if ($content->type !== 'newsletter') {
                return $this->respondBadRequest('Issues can only be created for newletters');
            }

            $issue = $content->issues()->create([
                'title' => $request->title,
                'description' => $request->description,
                'is_available' => 0,
            ]);

            return $this->respondWithSuccess('Issue has been created successfully', [
                'issue' => $issue->with('content')->first(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function updateIssue(Request $request, $id)
    {
        try {
            $validator1 = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:contents,id'],
                'issue_id' => ['required', 'string', 'exists:content_issues,id'],
                'title' => ['sometimes', 'nullable', 'string', 'max:200', 'min:1'],
                'description' => ['sometimes', 'nullable', 'string'],
            ]);

            if ($validator1->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator1->errors()->toArray());
            }

            $content = Content::where('id', $id)->where('user_id', $request->user()->id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest('You do not have permission to create an issue for this content');
            }

            if ($content->type !== 'newsletter') {
                return $this->respondBadRequest('Issues can only be created or updated for newletters');
            }

            $issue = $content->issues()->where('id', $request->issue_id)->first();

            if (! is_null($request->title)) {
                $issue->title = $request->title;
            }

            if (! is_null($request->description)) {
                $issue->description = $request->description;
            }

            $issue->save();

            return $this->respondWithSuccess('Issue has been updated successfully', [
                'issue' => $issue->with('content')->first(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function publishIssue(Request $request, $id)
    {
        try {
            $validator1 = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:contents,id'],
                'issue_id' => ['required', 'string', 'exists:content_issues,id'],
            ]);

            if ($validator1->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator1->errors()->toArray());
            }

            $content = Content::where('id', $id)->where('user_id', $request->user()->id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest('You do not have permission to create an issue for this content');
            }

            if ($content->type !== 'newsletter') {
                return $this->respondBadRequest('Issues can only be created or updated for newletters');
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
                'issue' => $issue->with('content')->first(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'string', 'exists:contents,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            $content = Content::where('id', $id)
            ->eagerLoadBaseRelations($user_id)
            ->eagerLoadSingleContentRelations($user_id)
            ->first();
            return $this->respondWithSuccess('Content retrieved successfully', [
                'content' => new ContentResource($content),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getContentInsights(Request $request, $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'string', 'exists:contents,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();

            if ($request->user()->id !== $content->user_id) {
                return $this->respondBadRequest('Only owner of content can view this information');
            }

            return $this->respondWithSuccess('Insights retrieved successfully', [
                'all_time_views' => $content->views()->count(),
                'year_views' => $content->views()->whereDate('created_at', '>=', now()->startOfYear())->count(),
                'month_views' => $content->views()->whereDate('created_at', '>=', now()->startOfMonth())->count(),
                'day_views' => $content->views()->whereDate('created_at', '=', today())->count(),
                'all_time_sales' => $content->revenues()->where('revenue_from', 'sale')->count(),
                'year_sales' => $content->revenues()->where('revenue_from', 'sale')->whereDate('created_at', '>=', now()->startOfYear())->count(),
                'month_sales' => $content->revenues()->where('revenue_from', 'sale')->whereDate('created_at', '>=', now()->startOfMonth())->count(),
                'day_sales' => $content->revenues()->where('revenue_from', 'sale')->whereDate('created_at', '>=', today())->count(),
                'subscribers' => $content->subscribers()->count(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function listTrending(Request $request)
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
            $orderDirection = $request->query('order_direction', 'asc');

            $activeLiveContent = $request->query('active_live_content', 'false');

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
                'type' => $types,
                'active_live_content' => $activeLiveContent,
            ], [
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
                'active_live_content' => ['sometimes', 'in:true,false'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
            
            $contents = Content::where('is_available', 1)
            ->whereNull('archived_at')
            ->where('is_adult', 0)
            ->where('approved_by_admin', 1)
            ->whereHas('digiverses', function (Builder $query) {
                $query->where('is_available', 1)
                ->where('is_adult', 0)
                ->where('approved_by_admin', 1);
            })->where(function ($query) {
                $query->whereNull('live_ended_at')->orWhereDate('live_ended_at', '>=', now()->subHours(12));
            });

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            if (! empty($keywords)) {
                $contents = $contents->where(function ($query) use ($keywords) {
                    $query->where('title', 'LIKE', "%{$keywords[0]}%")
                    ->orWhere('description', 'LIKE', "%{$keywords[0]}%");
                    for ($i = 1; $i < count($keywords); $i++) {
                        $query->orWhere('title', 'LIKE', "%{$keywords[$i]}%")
                            ->orWhere('description', 'LIKE', "%{$keywords[$i]}%");
                    }
                });
            }

            if (! empty($types)) {
                $contents = $contents->whereIn('type', $types);
            }

            if (! empty($tags)) {
                $contents = $contents->whereHas('tags', function (Builder $query) use ($tags) {
                    $query->whereIn('tags.id', $tags);
                });
            }

            if (! empty($creators)) {
                $contents = $contents->whereIn('user_id', $creators);
            }

            if ($activeLiveContent === 'true') {
                $contents = $contents->where(function ($query) {
                    $query->where('live_status', 'active')
                    ->orWhere('live_status', 'inactive');
                });
            }

            $contents = $contents
            ->eagerLoadBaseRelations($user_id)
            ->orderBy('contents.trending_points', 'desc')
            ->orderBy("contents.{$orderBy}", $orderDirection)
            ->paginate($limit, ['*'], 'page', $page);

            return $this->respondWithSuccess('Contents retrieved successfully', [
                'contents' => ContentResource::collection($contents),
                'current_page' => (int) $contents->currentPage(),
                'items_per_page' => (int) $contents->perPage(),
                'total' => (int) $contents->total(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function listContents(Request $request, $collection_id)
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
                'id' => $collection_id,
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
                'active_live_content' => $activeLiveContent,
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
                'active_live_content' => ['sometimes', 'in:true,false'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
            $collection = Collection::where('id', $request->collection_id)->first();
            // TO DO: add test to show lives ended more than 12 hours ago do not get returned
            $contents = $collection->contents()
                            ->whereNull('archived_at')
                            ->where(function ($query) {
                                $query->whereNull('live_ended_at')->orWhereDate('live_ended_at', '>=', now()->subHours(12));
                            });

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            if ($user_id !== $collection->user_id) {
                $contents = $contents->where('is_available', 1)->where('approved_by_admin', 1);
            }

            if (! empty($keywords)) {
                $contents = $contents->where(function ($query) use ($keywords) {
                    $query->where('title', 'LIKE', "%{$keywords[0]}%")
                    ->orWhere('description', 'LIKE', "%{$keywords[0]}%");
                    for ($i = 1; $i < count($keywords); $i++) {
                        $query->orWhere('title', 'LIKE', "%{$keywords[$i]}%")
                            ->orWhere('description', 'LIKE', "%{$keywords[$i]}%");
                    }
                });
            }

            if (! empty($types)) {
                $contents = $contents->whereIn('type', $types);
            }

            if (! empty($tags)) {
                $contents = $contents->whereHas('tags', function (Builder $query) use ($tags) {
                    $query->whereIn('tags.id', $tags);
                });
            }

            if (! empty($creators)) {
                $contents = $contents->whereIn('user_id', $creators);
            }

            if ($activeLiveContent === 'true') {
                $contents = $contents->where(function ($query) {
                    $query->where('live_status', 'active')
                    ->orWhere('live_status', 'inactive');
                });
            }

            $contents = $contents
            ->eagerLoadBaseRelations($user_id)
            ->orderBy("contents.{$orderBy}", $orderDirection)
            ->paginate($limit, ['*'], 'page', $page);

            return $this->respondWithSuccess('Contents retrieved successfully', [
                'contents' => ContentResource::collection($contents),
                'current_page' => (int) $contents->currentPage(),
                'items_per_page' => (int) $contents->perPage(),
                'total' => (int) $contents->total(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function getIssues(Request $request, $id)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);

            $keyword = urldecode($request->query('keyword', ''));
            $keywords = explode(' ', $keyword);
            $keywords = array_diff($keywords, ['']);

            $max_items_count = Constants::MAX_ITEMS_LIMIT;

            $validator = Validator::make([
                'id' => $id,
                'page' => $page,
                'limit' => $limit,
                'keyword' => $keyword,
            ], [
                'id' => ['required', 'string', 'exists:contents,id'],
                'page' => ['required', 'integer', 'min:1'],
                'limit' => ['required', 'integer', 'min:1', "max:{$max_items_count}"],
                'keyword' => ['sometimes', 'string', 'max:200'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }
            $content = Content::where('id', $id)->first();
            $issues = $content->issues();
            if ($request->user()->id !== $content->user_id) {
                $issues = $issues->where('is_available', 1);
            }

            if (! empty($keywords)) {
                $issues = $issues->where(function ($query) use ($keywords) {
                    $query->where('title', 'LIKE', "%{$keywords[0]}%")
                    ->orWhere('description', 'LIKE', "%{$keywords[0]}%");
                    for ($i = 1; $i < count($keywords); $i++) {
                        $query->orWhere('title', 'LIKE', "%{$keywords[$i]}%")
                            ->orWhere('description', 'LIKE', "%{$keywords[$i]}%");
                    }
                });
            }

            $issues = $issues
            ->with('content')
            ->orderBy('content_issues.created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
            return $this->respondWithSuccess('Issues retrieved successfully', [
                'issues' => ContentIssueResource::collection($issues),
                'current_page' => (int) $issues->currentPage(),
                'items_per_page' => (int) $issues->perPage(),
                'total' => (int) $issues->total(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
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
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $issue = ContentIssue::where('id', $id)->first();
            return $this->respondWithSuccess('Issue retrieved successfully', [
                'issue' => new ContentIssueResource($issue->with('content')->first()),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
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
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();
            $content->subscribers()->syncWithoutDetaching([
                $request->user()->id => [
                    'id' => Str::uuid(),
                ],
            ]);
            return $this->respondWithSuccess('You have been successfully subscribed');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
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
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();
            $content->subscribers()->detach([$request->user()->id]);
            return $this->respondWithSuccess('You have successfully unsubscribed');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
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
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();
            if ($content->type !== 'live-video' && $content->type !== 'live-audio') {
                return $this->respondBadRequest('Live broadcasts can only be started for live content types');
            }

            if ($content->user_id !== $request->user()->id) {
                return $this->respondBadRequest('Only the creator can start the live broadcast');
            }

            $channel = $content->metas()->where('key', 'channel_name')->first();
            if (is_null($channel)) {
                $channel = $content->metas()->create([
                    'key' => 'channel_name',
                    'value' => "{$content->id}-" . date('Ymd'),
                ]);
            }
            $rtc_token = $content->metas()->where('key', 'rtc_token')->first();
            if (is_null($rtc_token)) {
                $rtc_token = $content->metas()->create([
                    'key' => 'rtc_token',
                    'value' => '',
                ]);
            }
            $rtm_token = $content->metas()->where('key', 'rtm_token')->first();
            if (is_null($rtm_token)) {
                $rtm_token = $content->metas()->create([
                    'key' => 'rtm_token',
                    'value' => '',
                ]);
            }
            $join_count = $content->metas()->where('key', 'join_count')->first();
            if (is_null($join_count)) {
                $join_count = $content->metas()->create([
                    'key' => 'join_count',
                    'value' => 0,
                ]);
            }
            //ensure that the live has not been started before
            if ($content->live_status === 'active') {
                return $this->respondWithSuccess('Channel started successfully', [
                    'rtc_token' => $rtc_token->value,
                    'rtm_token' => $rtm_token->value,
                    'channel_name' => $channel->value,
                    'uid' => 0,
                ]);
            }

            $expires = time() + (24 * 60 * 60); // let token last for 24hrs
            $agora_rtc_token = AgoraRtcToken::buildTokenWithUid(config('services.agora.id'), config('services.agora.certificate'), $channel->value, 0, AgoraRtcToken::ROLE_PUBLISHER, $expires);

            $agora_rtm_token = AgoraRtmToken::buildToken(config('services.agora.id'), config('services.agora.certificate'), $channel->value, 0, AgoraRtmToken::ROLE_RTM_USER, $expires);

            $rtc_token->value = $agora_rtc_token;
            $rtc_token->save();

            $rtm_token->value = $agora_rtm_token;
            $rtm_token->save();

            $join_count->value = 1;
            $join_count->save();

            $content->live_status = 'active';
            $content->scheduled_date = now();
            $content->save();

            DispatchNotificationToFollowersJob::dispatch([
                'notificable_id' => $content->id,
                'notificable_type' => 'content',
                'user' => $request->user(),
                'notifier' => $content->owner,
                'message' => "@{$request->user()->username} has started a new live",
            ]);

            return $this->respondWithSuccess('Channel started successfully', [
                'rtc_token' => $rtc_token->value,
                'rtm_token' => $rtm_token->value,
                'channel_name' => $channel->value,
                'uid' => 0,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
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
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();
            if ($content->type !== 'live-video' && $content->type !== 'live-audio') {
                return $this->respondBadRequest('Live broadcasts can only be joined for live content types');
            }

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            if (! $content->isFree() && ! $content->userHasPaid($user_id) && ! ($content->user_id == $user_id)) {
                return $this->respondBadRequest('You do not have access to this live because you have not purchased it');
            }

            $channel = $content->metas()->where('key', 'channel_name')->first();
            $rtc_token = $content->metas()->where('key', 'rtc_token')->first();
            $rtm_token = $content->metas()->where('key', 'rtm_token')->first();
            if (is_null($rtc_token) || $rtc_token->value == '' || is_null($rtm_token) || $rtm_token->value == '') {
                return $this->respondBadRequest('You cannot join a broadcast that has not been started');
            }
            if ($content->live_status !== 'active') {
                return $this->respondBadRequest('You cannot join a broadcast that has not been started');
            }

            if ($user_id !== '') {
                $content->subscribers()->syncWithoutDetaching([
                    $request->user()->id => [
                        'id' => Str::uuid(),
                    ],
                ]);
            }
            
            $join_count = $content->metas()->where('key', 'join_count')->first();
            $uid = $join_count->value;
            $join_count->value = (int) $join_count->value + 1;
            $join_count->save();
            $expires = time() + (24 * 60 * 60); // let token last for 24hrs
            // $token = AgoraRtcToken::buildTokenWithUid(env('AGORA_APP_ID'), env('AGORA_APP_CERTIFICATE'), $channel->value, $uid, AgoraRtcToken::ROLE_ATTENDEE, $expires);
           // $subscribers_count = $content->subscribers()->count();
            $websocket_client = new \WebSocket\Client(config('services.websocket.url'));
            $websocket_client->text(json_encode([
                'event' => 'app-update-rtm-channel-subscribers-count',
                'channel_name' => $channel->value,
                'subscribers_count' => (int) $join_count->value,
                'source_type' => 'app',
            ]));
            $websocket_client->close();

            return $this->respondWithSuccess('Channel joined successfully', [
                'rtc_token' => $rtc_token->value,
                'rtm_token' => $rtm_token->value,
                'channel_name' => $channel->value,
                'uid' => (int) $uid,
                'subscribers_count' => (int) $join_count->value,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
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
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();
            if ($content->type !== 'live-video' && $content->type !== 'live-audio') {
                return $this->respondBadRequest('Live broadcasts can only be left for live content types');
            }
            $content->subscribers()->detach([$request->user()->id]);

            return $this->respondWithSuccess('Channel left successfully');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
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
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();
            if ($content->type !== 'live-video' && $content->type !== 'live-audio') {
                return $this->respondBadRequest('Live broadcasts can only be ended for live content types');
            }

            if ($content->user_id !== $request->user()->id) {
                return $this->respondBadRequest('Only the creator can start the live broadcast');
            }

            if ($content->live_status !== 'active') {
                return $this->respondWithSuccess('Channel ended successfully');
            }

            $content->live_status = 'ended';
            $content->live_ended_at = now();
            $content->save();

            DispatchDisableLiveUserableJob::dispatch([
                'live_content' => $content,
            ]);
            return $this->respondWithSuccess('Channel ended successfully');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function listReviews(Request $request, $id)
    {
        try {
            $content = Content::where('id', $id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest('Invalid content ID supplied');
            }
            $page = ctype_digit(strval($request->query('page', 1))) ? $request->query('page', 1) : 1;
            $limit = ctype_digit(strval($request->query('limit', 10))) ? $request->query('limit', 10) : 1;
            if ($limit > Constants::MAX_ITEMS_LIMIT) {
                $limit = Constants::MAX_ITEMS_LIMIT;
            }

            $reviews = $content->reviews()->with('user', 'user.profile_picture', 'user.roles')->orderBy('created_at', 'desc')->paginate($limit, ['*'], 'page', $page);
            return $this->respondWithSuccess('Reviews retrieved successfully', [
                'reviews' => $reviews,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function listAssets(Request $request, $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'string', 'exists:contents,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = '';
            } else {
                $user_id = $request->user()->id;
            }

            $content = Content::where('id', $id)->first();

            if (! $content->isFree() && ! $content->userHasPaid($user_id) && ! ($content->user_id == $user_id)) {
                return $this->respondBadRequest('You are not permitted to view the assets of this content');
            }
            // get signed cookies
            $cloudFrontClient = new CloudFrontClient([
                'profile' => 'default',
                'version' => '2014-11-06',
                'region' => 'us-east-1',
            ]);

            $expires = time() + (2 * 60 * 60); //2 hours from now(in seconds)
            $resource = config('flok.private_media_url') . '/*';
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
                'private_key' => base64_decode(config('services.cloudfront.private_key')),
                'key_pair_id' => config('services.cloudfront.key_id'),
            ]);
            $cookies = '';
            foreach ($result as $key => $value) {
                $cookies = $cookies . $key . '=' . $value . ';';
                $secure = true;
                $path = '/';
                $domain = '.tryflok.com';
                $time_in_minutes = 2 * 60;
                Cookie::queue($key, $value, $time_in_minutes, $path, $domain, $secure);
            }
            return $this->respondWithSuccess('Assets retrieved successfully', [
                'assets' => $content->assets()->with('resolutions')->wherePivot('purpose', 'content-asset')->get(),
                'cookies' => $cookies,
                'cookies_expire' => $expires,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function addViews(Request $request, $id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => ['required', 'string', 'exists:contents,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            if ($request->user() == null || $request->user()->id == null) {
                $user_id = null;
            } else {
                $user_id = $request->user()->id;
            }

            $content = Content::where('id', $id)->first();

            $content->views()->create([
                'user_id' => $user_id,
            ]);

            $content = $content
            ->eagerLoadBaseRelations()
            ->first();

            return $this->respondWithSuccess('View recorded successfully', [
                'content' => new ContentResource($content),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function respondToChallenge(Request $request, $id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:contents,id'],
                'action' => ['required', 'string', 'in:accept,decline'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();

            $contestant = $content->challengeContestants()->where('user_id', $request->user()->id)->first();
            if (is_null($contestant)) {
                return $this->respondBadRequest('You cannot respond to this challenge because you are not a contestant');
            }

            switch ($request->action) {
                case 'accept':
                    $contestant->status = 'accepted';
                    $contestant->save();
                    NotifyChallengeResponseJob::dispatch($content, $request->user(), $contestant->status);
                    break;
                case 'decline':
                    $contestant->status = 'declined';
                    $contestant->save();
                    NotifyChallengeResponseJob::dispatch($content, $request->user(), $contestant->status);
                    break;
            }

            $content = $content
            ->eagerLoadBaseRelations()
            ->eagerLoadSingleContentRelations()
            ->first();

            return $this->respondWithSuccess('Your response has been recorded successfully', [
                'content' => new ContentResource($content),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function contributeToChallenge(Request $request, $id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:contents,id'],
                'amount' => ['required', 'integer'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();

            if ((int) $content->is_challenge !== 1) {
                return $this->respondBadRequest('You can only contribute to a challenge');
            }

            $user = $request->user();

            $all_contestants_accepted = true;
            foreach ($content->challengeContestants as $contestant) {
                if ($contestant->status !== 'accepted') {
                    $all_contestants_accepted = false;
                    break;
                }
            }

            if (! $all_contestants_accepted) {
                return $this->respondBadRequest('Both contestants need to accept this challenge before you can contribute to it');
            }
            
            if ($content->live_status === 'ended') {
                return $this->respondBadRequest('You cannot contribute to a challenge that has ended');
            }

            $pot_size = $content->metas()->where('key', 'pot_size')->first();
            if ((int) $pot_size->value === 0) {
                return $this->respondBadRequest('You cannot contribute to a challenge with a pot size of 0');
            }

            $minimum_contribution = $content->metas()->where('key', 'minimum_contribution')->first();
            $previous_contribution = $content->challengeContributions()->where('user_id', $user->id)->first();
            $total_contribution_amount = (int) $request->amount;
            if (! is_null($previous_contribution)) {
                $total_contribution_amount += (int) $previous_contribution->amount;
            }

            if ((int) $minimum_contribution->value > (int) $total_contribution_amount) {
                return $this->respondBadRequest("Contribution amount must be at least {$minimum_contribution->value} Flok Cowries");
            }

            if ((int) $request->amount > (int) $user->wallet->balance) {
                return $this->respondBadRequest("You do not have enough Flok Cowries to contribute to this challenge");
            }
            
            $new_wallet_balance = bcsub($user->wallet->balance, $request->amount, 2);
            WalletTransaction::create([
                'wallet_id' => $user->wallet->id,
                'amount' => $request->amount,
                'balance' => $new_wallet_balance,
                'transaction_type' => 'deduct',
                'details' => "Withdrawal from wallet to contribute to {$content->title} challenge",
            ]);
            $user->wallet->balance = $new_wallet_balance;
            $user->wallet->save();

            if (! is_null($previous_contribution)) {
                $previous_contribution->amount = $total_contribution_amount;
                $previous_contribution->save();
            } else {
                $content->challengeContributions()->create([
                    'user_id' => $user->id,
                    'amount' => $request->amount,
                ]);
            }

            $content = $content
            ->eagerLoadBaseRelations()
            ->eagerLoadSingleContentRelations()
            ->first();

            return $this->respondWithSuccess('Your contribution has been recorded successfully', [
                'content' => new ContentResource($content),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function voteOnChallenge(Request $request, $id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string', 'exists:contents,id'],
                'contestant' => ['required', 'string', 'exists:users,id'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->first();

            if ((int) $content->is_challenge !== 1) {
                return $this->respondBadRequest('You can only vote on a challenge');
            }

            if (is_null($content->live_ended_at)) {
                return $this->respondBadRequest('You can only vote after the challenge has ended');
            }

            $voting_window = Constants::CHALLENGE_VOTE_WINDOW_IN_MINUTES;
            if ($content->live_ended_at->lte(now()->subMinutes($voting_window))) {
                return $this->respondBadRequest("You can only vote whithin the first {$voting_window} minutes after the live has ended");
            }

            $contestant = $content->challengeContestants()->where('user_id', $request->contestant)->first();
            if (is_null($contestant)) {
                return $this->respondBadRequest('You can only vote for users listed as contestants in the challenge');
            }

            $user = $request->user();
            $user_contributed = false;
            $contribution = $content->challengeContributions()->where('user_id', $user->id)->first();
            if (! is_null($contribution)) {
                $user_contributed = true;
            }

            $post_size = $content->metas()->where('key', 'pot_size')->first();

            $user_vote = $content->challengeVotes()->where('voter_id', $user->id)->first();

            if ((int) $post_size->value > 0 && $user_contributed === false) {
                return $this->respondBadRequest('You can only vote on this challenge if you contribute to the pot');
            }
 
            if (! is_null($user_vote)) {
                return $this->respondBadRequest('You have already cast a vote before');
            }
            
            $content->challengeVotes()->create([
                'voter_id' => $user->id,
                'contestant_id' => $request->contestant,
            ]);

            $content = $content
            ->eagerLoadBaseRelations()
            ->eagerLoadSingleContentRelations()
            ->first();

            return $this->respondWithSuccess('Your vote has been recorded successfully', [
                'content' => new ContentResource($content),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function proxyAsset(Request $request, $path)
    {
        try {
            $headers = $request->header();
            $x_cookie = '';
            if (array_key_exists('x-cookie', $headers)) {
                $x_cookie = $headers['x-cookie'][0];
            }
            $cloudfront_url = join_path(config('flok.private_media_url'), $path);
            $client = new GuzzleClient;
            $response = $client->get($cloudfront_url, [
                'headers' => [
                    'Cookie' => $x_cookie,
                ],
            ]);

            $headers = $response->getHeaders();
            $content_type = "text/html";
            foreach ($headers as $name => $value) {
                if (strtolower($name) === 'content-type') {
                    $content_type = $value[0];
                }
            }
            return response($response->getBody())->header('Content-Type', $content_type);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function createPoll(Request $request, $id)
    {
        try {
            $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                'id' => ['required', 'string'],
                'question' => ['required', 'string', 'max:200', 'min:1'],
                'closes_at' => ['required'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $content = Content::where('id', $id)->where('user_id', $request->user()->id)->first();
            if (is_null($content)) {
                return $this->respondBadRequest('You do not have permission to create an issue for this content');
            }

            $poll = $content->polls()->create([
                'question' => $request->question,
                'closes_at' => $request->closes_at,
                'content_id' => $content->id,
                'user_id' => $content->user_id,
            ]);

            $input = $request->all();
            for ($i = 0; $i <= count($input['option']); $i++)
            {
                $options = [
                        'content_poll_id' => $poll->id,
                        'option' => $input['option'][$i],
                ];
            }

            $content->polls->options()->createMany($options);

            return $this->respondWithSuccess('Poll has been created successfully', [
                'poll' => $poll->with('content')->first(),
            ]);

        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

}
