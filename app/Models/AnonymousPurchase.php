<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class AnonymousPurchase extends Model
{
    use HasFactory;
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
    protected $hidden = [
        'id',
    ];

    protected $guard_name = 'api';

    public function anonymous_purchaseable()
    {
        return $this->morphTo(AnonymousPurchase::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'anonymous_purchaseable_id');
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'anonymous_purchaseable_id')->where('status', 'active');
    }
}
