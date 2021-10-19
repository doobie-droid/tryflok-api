<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Uuid;

class Content extends Model
{
    use HasFactory, SoftDeletes, Uuid;

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

    public function subscriptions()
    {
        return $this->morphMany(Subscription::class, 'subscriptionable');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
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
    
    public function sales()
    {
        return $this->morphMany(Sale::class, 'saleable');
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

    public function carts()
    {
        return $this->morphToMany(Cart::class, 'cartable');
    }

    public function collections()
    {
        return $this->belongsToMany(Collection::class);
    }
}
