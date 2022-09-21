<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContentPoll extends Model
{   

    use Uuid;
    use HasFactory;

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
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
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
