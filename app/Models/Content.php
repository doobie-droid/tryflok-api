<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuid;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
    ];

     /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'scheduled_date' => 'datetime',
        'live_ended_at' => 'datetime',
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    protected $guard_name = 'api';

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function issues()
    {
        return $this->hasMany(ContentIssue::class, 'content_id');
    }

    public function subscribers()
    {
        return $this->belongsToMany(User::class, 'content_subscriber', 'content_id', 'user_id');
    }

    public function prices()
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function subscriptions()
    {
        return $this->morphMany(Subscription::class, 'subscriptionable');
    }

    public function views()
    {
        return $this->morphMany(View::class, 'viewable');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function notifiers()
    {
        return $this->morphMany(Notification::class, 'notificable');
    }

    public function metas()
    {
        return $this->morphMany(Meta::class, 'metaable');
    }

    public function assets()
    {
        return $this->morphToMany(Asset::class, 'assetable')->wherePivot('purpose', 'content-asset');
    }

    public function cover()
    {
        return $this->morphToMany(Asset::class, 'assetable')->wherePivot('purpose', 'cover');
    }

    public function approvalRequest()
    {
        return $this->morphOne(Approval::class, 'approvable');
    }

    public function benefactors()
    {
        return $this->morphMany(Benefactor::class, 'benefactable');
    }

    public function revenues()
    {
        return $this->morphMany(Revenue::class, 'revenueable');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function ratings()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function userables()
    {
        return $this->morphMany(Userable::class, 'userable');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function categories()
    {
        return $this->morphToMany(Category::class, 'categorable');
    }

    public function carts()
    {
        return $this->morphToMany(Cart::class, 'cartable');
    }

    public function likes()
    {
        return $this->hasMany(ContentLike::class, 'content_id');                                                                                                                                                                                                                                                                             Many(Like::class, 'likeable');
    }

    public function comments()
    {
        return $this->hasMany(ContentComment::class, 'content_id');                                                                                                                                                                                                                                                                             Many(Like::class, 'likeable');
    }

    public function collections()
    {
        return $this->belongsToMany(Collection::class);
    }

    public function basicCollections()
    {
        return $this->belongsToMany(Collection::class)->where('type', 'collection');
    }

    public function digiverses()
    {
        return $this->belongsToMany(Collection::class)->where('type', 'digiverse');
    }

    public function access_through_ancestors()
    {
        return $this->belongsToMany(Collection::class);
    }

    public function challengeContestants()
    {
        return $this->hasMany(ContentChallengeContestant::class);
    }

    public function challengeContributions()
    {
        return $this->hasMany(ContentChallengeContribution::class);
    }

    public function challengeVotes()
    {
        return $this->hasMany(ContentChallengeVote::class);
    }

    public function liveHosts()
    {
        return $this->hasMany(ContentLiveHost::class);
    }

    public function liveBroadcasters()
    {
        return $this->hasMany(ContentLiveBroadcaster::class);
    }

    public function polls()
    {
        return $this->hasMany(ContentPoll::class);
    }

    public function ContentPolls()
    {
        return $this->hasMany(ContentPoll::class);
    }
    public function isFree()
    {
        $freePriceCount = $this->prices()->where('amount', 0)->count();
        $parentPaidPriceCount = $this->collections()->whereHas('prices', function (Builder $query) {
            $query->where('amount', '>', 0);
        })->count();
        $grandParentPaidPriceCount = $this->collections()->whereHas('parentCollections', function (Builder $query) {
            $query->whereHas('prices', function (Builder $query) {
                $query->where('amount', '>', 0);
            });
        })->count();
        return $freePriceCount > 0 && $parentPaidPriceCount === 0 && $grandParentPaidPriceCount === 0;
    }

    public function userHasPaid($user_id)
    {
        $userablesCount = $this->userables()->where('status', 'available')->where('user_id', $user_id)->count();
        $parentUserablesCount = $this->collections()->whereHas('userables', function (Builder $query) use ($user_id) {
            $query->where('status', 'available')->where('user_id', $user_id);
        })->count();
        $grandParentUserablesCount = $this->collections()->whereHas('parentCollections', function (Builder $query) use ($user_id) {
            $query->whereHas('userables', function (Builder $query) use ($user_id) {
                $query->where('status', 'available')->where('user_id', $user_id);
            });
        })->count();
        return $userablesCount > 0 || $parentUserablesCount > 0 || $grandParentUserablesCount > 0;
    }

    public function scopeEagerLoadBaseRelations($mainQuery, string $user_id = '')
    {
        return $mainQuery->withCount('subscribers')
        ->withCount([
            'revenues' => function ($query) {
                $query->where('revenue_from', 'sale');
            },
        ])
        ->withCount('likes')
        ->withCount('views')
        ->with('metas')
        ->with('collections', 'collections.prices')
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
        ->with([
            'owner' => function ($query) {
                $query->with('profile_picture')
                ->withCount('followers', 'following');
            },
        ])
        ->with('tags')
        ->with('prices')
        ->with([
            'userables' => function ($query) use ($user_id) {
                $query->with('subscription')->where('user_id', $user_id)->where('status', 'available');
            },
        ])
        ->with([
            'likes' => function ($query) use ($user_id) {
                $query->where('user_id', $user_id); 
            },
        ])
        ->with([
            'polls', 
            'polls.pollOptions' => function($query) use ($user_id){
                $query->withCount('votes')
                           ->with(['votes' => function ($sub_query) use ($user_id) {
                                  $sub_query->where('voter_id', $user_id);
                 }]);
            },
        ])
        ->with([
            'access_through_ancestors' => function ($query) use ($user_id) {
                $query->whereHas('userables', function (Builder $query) use ($user_id) {
                    $query->where('user_id', $user_id)->where('status', 'available');
                })
                ->orWhereHas('parentCollections', function (Builder $query) use ($user_id) {
                    $query->whereHas('userables', function (Builder $query) use ($user_id) {
                        $query->where('user_id', $user_id)->where('status', 'available');
                    });
                });
            },
        ]);
    }

    public function scopeEagerLoadSingleContentRelations($mainQuery, string $user_id = '')
    {
        return $mainQuery->with([
            'subscribers' => function ($query) use ($user_id) {
                $query->where('users.id', $user_id);
            },
        ])
        ->with([
            'polls', 
            'polls.pollOptions' => function($query) use ($user_id){
                $query->withCount('votes')
                           ->with(['votes' => function ($sub_query) use ($user_id) {
                                  $sub_query->where('voter_id', $user_id);
                 }]);
            },
        ])
        ->withSum('challengeContributions', 'amount')
        ->with([
            'challengeContestants' => function ($query) {
                $query->with('contestant', 'contestant.profile_picture');
            },
        ])->with([
            'liveBroadcasters' => function ($query) {
                $query->with('broadcaster', 'broadcaster.profile_picture');
            },
        ]);
    }
}