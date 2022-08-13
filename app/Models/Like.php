<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use Uuid;
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    public function contents()
    {
        return $this->morphTo(Content::class, 'likeable');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
