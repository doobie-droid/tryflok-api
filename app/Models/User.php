<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use SoftDeletes;
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
    protected $hidden = [
        'password',
        'remember_token',
        'password_token',
        'email_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    protected $guard_name = 'api';

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function assets()
    {
        return $this->morphToMany(Asset::class, 'assetable');
    }

    public function profile_picture()
    {
        return $this->morphToMany(Asset::class, 'assetable')->wherePivot('purpose', 'profile-picture');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function contentsPaidFor()
    {
        return $this->morphedByMany(Content::class, 'userable');
    }

    public function collectionsPaidFor()
    {
        return $this->morphedByMany(Collection::class, 'userable');
    }

    public function digiversesPaidFor()
    {
        return $this->morphedByMany(Collection::class, 'userable')->where('type', 'digiverse');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'recipient_id');
    }

    public function notificationTokens()
    {
        return $this->hasMany(NotificationToken::class);
    }

    public function revenues()
    {
        return $this->hasMany(Revenue::class, 'user_id');
    }

    public function contentsCreated()
    {
        return $this->hasMany(Content::class);
    }

    public function collectionsCreated()
    {
        return $this->hasMany(Collection::class);
    }

    public function digiversesCreated()
    {
        return $this->hasMany(Collection::class)->where('type', 'digiverse');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    public function paymentAccounts()
    {
        return $this->hasMany(PaymentAccount::class);
    }

    public function paymentsMade()
    {
        return $this->hasMany(Payment::class, 'payer_id');
    }

    public function paymentsReceived()
    {
        return $this->hasMany(Payment::class, 'payee_id');
    }

    public function otps()
    {
        return $this->hasMany(Otp::class);
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'walletable');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'follower_id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'user_id');
    }

    public function contentPollVote()
    {
        return $this->hasOne(ContentPollVote::class, 'voter_id');
    }

    public function contentLikes()
    {
        return $this->hasMany(ContentLike::class, 'content_id');
    }

    public function contentComments()
    {
        return $this->hasMany(ContentComment::class, 'user_id');
    }

    public function contentCommentComments()
    {
        return $this->hasMany(ContentCommentComment::class, 'user_id');
    }

    public function scopeEagerLoadBaseRelations($mainQuery, string $user_id = '')
    {
        return $mainQuery
        ->with('roles', 'profile_picture')
            ->withCount('digiversesCreated')
            ->with([
                'followers' => function ($query) use ($user_id) {
                    $query->where('users.id', $user_id);
                },
                'following' => function ($query) use ($user_id) {
                    $query->where('users.id', $user_id);
                },
            ])
            ->withCount('followers', 'following');     
    }
}
