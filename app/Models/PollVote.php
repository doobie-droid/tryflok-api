<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollVote extends Model
{   
    use Uuid;
    use HasFactory;

    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }
}
