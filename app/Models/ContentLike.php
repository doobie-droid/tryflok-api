<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentLike extends Model
{
    use Uuid;
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
