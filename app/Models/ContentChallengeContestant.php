<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentChallengeContestant extends Model
{
    use HasFactory, Uuid;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    protected $guard_name = 'api';

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function contestant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
