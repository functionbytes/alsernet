<?php

namespace Modules\Auth\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\PasswordHistory;
use Modules\Auth\Models\Session;
use Modules\Auth\Models\TrustedDevice;

/**
 * Trait HasBasicRelations
 *
 * Gestiona las relaciones básicas del modelo User que no caen
 * en otras categorías específicas.
 */
trait HasBasicRelations
{
    /**
     * Get user's sessions
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'user_id');
    }

    /**
     * Get user's most recent session
     */
    public function session(): HasOne
    {
        return $this->hasOne(Session::class, 'user_id')->latestOfMany('last_activity');
    }

    /**
     * Get user's trusted devices
     */
    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class, 'user_id');
    }

    /**
     * Get user's password history entries
     */
    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class, 'user_id');
    }

    /**
     * Get user's orders
     */
    public function orders(): HasMany
    {
        return $this->hasMany('App\Models\Order\Order', 'user_id');
    }

    /**
     * Get user's language
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo('App\Models\Lang');
    }

    /**
     * Get user's shop
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo('Modules\Catalog\Models\Shop', 'shop_id', 'id');
    }

    /**
     * Get user relationship (recursive)
     */
    public function user(): HasOne
    {
        return $this->hasOne('App\Models\User');
    }

    /**
     * Get authenticated user statically
     */
    public static function auth()
    {
        return Auth::user();
    }

    /**
     * Check if user exists by UID
     */
    public static function existence($uid)
    {
        return self::where('uid', '=', $uid)->first();
    }

    /**
     * Get redirect route name based on user role
     */
    public function redirectRouteName(): string
    {
        return 'core.dashboard';
    }

    /**
     * Redirect to user's dashboard
     */
    public function redirect()
    {
        return redirect()->route($this->redirectRouteName());
    }

    /**
     * Route to user's dashboard (alias)
     */
    public function route()
    {
        return redirect()->route($this->redirectRouteName());
    }
}
