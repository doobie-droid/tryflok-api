<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
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

    public function prices()
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function subscriptions()
    {
        return $this->morphMany(Subscription::class, 'subscriptionable');
    }

    public function assets()
    {
        return $this->morphToMany(Asset::class, 'assetable');
    }

    public function cover()
    {
        return $this->morphToMany(Asset::class, 'assetable')->wherePivot('purpose', 'cover');
    }

    public function benefactors()
    {
        return $this->morphMany(Benefactor::class, 'benefactable');
    }

    public function approvalRequest()
    {
        return $this->morphOne(Approval::class, 'approvable');
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

    public function categories()
    {
        return $this->morphToMany(Category::class, 'categorable');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function carts()
    {
        return $this->morphToMany(Cart::class, 'cartable');
    }

    public function contents()
    {
        return $this->belongsToMany(Content::class);
    }

    public function collections()
    {
        return $this->childCollections()->where('type', 'collection');
    }

    public function digiverses()
    {
        return $this->parentCollections()->where('type', 'digiverse');
    }

    public function childCollections()
    {
        return $this->belongsToMany(Collection::class, 'collection_collection', 'parent_id', 'child_id');
    }

    public function parentCollections()
    {
        return $this->belongsToMany(Collection::class, 'collection_collection', 'child_id', 'parent_id');
    }

    public function contentTypesAvailable($user_id)
    {
        $all_content_types = ['pdf', 'audio', 'video', 'newsletter', 'live-audio', 'live-video'];
        $content_types_available = [];
        foreach ($all_content_types as $content_type) {
            $ctc = $this->contents()->where('type', $content_type);
            if ($this->user_id !== $user_id) {
                $ctc = $ctc->where('is_available', 1);
            }
            $count = $ctc->count();
            if ($count > 0) {
                $content_types_available[] = $content_type;
            }
        }
        $child_collections = $this->childCollections();
        if ( $child_collections->count() > 0) {
            $content_types_available[] = 'collection';
        }
        return $content_types_available;
    }

    public function scopeEagerLoadBaseRelations($mainQuery, string $user_id = '')
    {
        return $mainQuery->withCount([
            'subscriptions' => function ($query) {
                $query->where('status', 'active');
            },
        ])
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
        ->withCount([
            'revenues' => function ($query) {
                $query->where('revenue_from', 'sale');
            },
        ])
        ->with('parentCollections')
        ->withCount('collections');
    }
}
