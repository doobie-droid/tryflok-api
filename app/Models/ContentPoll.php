<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class ContentPoll extends Model
{   

    use HasFactory;
    use HasRoles;
    use Uuid;

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
   
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $hidden = [];

    protected $guard_name = 'api';

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */

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
