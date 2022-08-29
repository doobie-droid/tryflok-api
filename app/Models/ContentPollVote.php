<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ContentPollVote extends Model
{   

    use Uuid;
    use HasFactory;

    public $timestamps = false;


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
        return $this->belongsTo(ContentPoll::class, 'content_poll_id');
    }

    public function pollOption()
    {
        return $this->belongsTo(ContentPollOption::class, 'content_poll_option_id');
    }

    public function voter()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
