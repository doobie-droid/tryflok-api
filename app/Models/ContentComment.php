<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentComment extends Model
{
    use HasFactory;
    use Uuid;
    use SoftDeletes;

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

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function comments()
    {
        return $this->hasMany(ContentCommentComment::class, 'content_comment_id');                                                                                                                                                                                                                                                                             Many(Like::class, 'likeable');
    }

}
