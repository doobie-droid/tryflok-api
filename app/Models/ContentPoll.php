<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class ContentPoll extends Model
{   

    use Uuid;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
    ];



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
