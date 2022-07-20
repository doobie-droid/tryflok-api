<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentPoll extends Model
{   
    use Uuid;
    use HasFactory;

    public function contents()
    {
        return $this->belongsToMany(Content::class);
    }

    public function contentPollOptions()
    {
        return $this->hasMany(ContentPollOption::class);
    }

    public function contentPollVotes()
    {
        return $this->hasMany(ContentPollVote::class);
    }
}
