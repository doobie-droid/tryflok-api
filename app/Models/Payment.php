<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class Payment extends Model
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

    public function paymentable()
    {
        return $this->morphTo();
    }

    public function payer()
    {
        return $this->belongsTo(User::class,'payer_id');
    }

    public function payee()
    {
        return $this->belongsTo(User::class,'payee_id');
    }
}
