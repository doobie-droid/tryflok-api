<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class ContentPollVote extends Model
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

    public function poll()
    {
        return $this->belongsTo(ContentPoll::class);
    }

    public function voter()
    {
        return $this->belongsTo(User::class);
    }
}
