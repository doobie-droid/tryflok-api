<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory, Uuid;

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

    public function subscriptionable()
    {
        return $this->morphTo();
    }

    public function userable()
    {
        return $this->belongsTo(Userable::class, 'userable_id');
    }

    public function price()
    {
        return $this->belongsTo(Price::class);
    }
}
