<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Userable extends Model
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
        'userable_id',
        'user_id',
    ];

    protected $guard_name = 'api';

    public function userable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'userable_id');
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'userable_id')->where('status', 'active');
    }
}
