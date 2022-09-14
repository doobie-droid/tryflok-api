<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class ContentCommentComment extends Model
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
    protected $hidden = [];

    protected $guard_name = 'api';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contentComments()
    {
        return $this->belongsTo(ContentComment::class);
    }
}
