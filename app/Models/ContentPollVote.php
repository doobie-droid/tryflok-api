<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentPollVote extends Model
{   
    use Uuid;
    use HasFactory;

    public function contentPoll()
    {
        return $this->belongsTo(ContentPoll::class);
    }

    public function voter()
    {
        return $this->belongsTo(User::class);
    }
}
