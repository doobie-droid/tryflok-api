<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Podcast extends Model
{
    use HasFactory;
    use Uuid;
    use SoftDeletes;



    protected $guarded = [
        'id',
    ];
    protected $hidden = [];

    public function digiverse()
    {
        return $this->hasOne(Collection::class, 'digiverse_id');
    }

    public function podcast()
    {
        return $this->hasOne(Collection::class, 'podcast_id');
    }
}
