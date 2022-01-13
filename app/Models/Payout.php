<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
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

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'last_payment_request' => 'datetime',
        'failed_notification_sent' => 'datetime',
    ];

    protected $guard_name = 'api';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function increasePayoutCashoutAttempts()
    {
        $this->cashout_attempts = (int) $this->cashout_attempts + 1;
        $this->save();
    }

    /** @param $buffer_time is time in hours */
    public function failedNotificationNotSent(int $buffer_time = 12): bool
    {
        $has_been_sent = false;
        if (
            is_null($this->failed_notification_sent) || 
            $this->failed_notification_sent->lte(now()->subHours($buffer_time))
        ) {
            $has_been_sent = true;
        }
        return $has_been_sent;
    }

    public function markAsCompleted(string $reference)
    {
        $this->reference = $reference;
        $this->claimed = 1;
        $this->save();
    }

    public function setHandler(string $handler)
    {
        $this->handler = $handler;
        $this->save();
    }

    public function resetCashoutAttept()
    {
        $this->payout->handler = null;
        $this->payout->save();
    }
}
