<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalMessage extends Model
{
    use HasFactory;
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
    protected $hidden = [];

    public function attachments()
    {
        return $this->morphMany(Asset::class, 'assetable');
    }

    public function approval()
    {
        return $this->belongsTo(Approval::class, 'approval_id');
    }
}
