<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    use Uuid;

    /**
    * The attributes that are not mass assignable.
    *
    * @var array
    */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    protected $guard_name = 'api';

    public function contents()
    {
        return $this->morphedByMany(Content::class, 'categorable');
    }

    public function collections()
    {
        return $this->morphedByMany(Collection::class, 'categorable');
    }
}
