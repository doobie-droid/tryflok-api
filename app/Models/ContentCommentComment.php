<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentCommentComment extends Model
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

    public function contentComment()
    {
        return $this->belongsTo(ContentComment::class);
    }

    public function likes()
    {
        return $this->hasMany(ContentCommentCommentLike::class, 'content_comment_comment_id');
    }
}
