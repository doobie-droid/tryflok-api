<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class ContentPollVote extends Model
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



    public function poll()
    {
        return $this->belongsTo(ContentPoll::class);
    }

    public function voter()
    {
        return $this->belongsTo(User::class);
    }
}
