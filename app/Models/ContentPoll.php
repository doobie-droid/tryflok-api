<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentPoll extends Model
{   
    use Uuid;
    use HasFactory;

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function pollOptions()
    {
        return $this->hasMany(ContentPollOption::class);
    }

    public function votes()
    {
        return $this->hasMany(ContentPollVote::class);
    }
}
