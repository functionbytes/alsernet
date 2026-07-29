<?php

namespace Modules\Auth\Traits;

use Illuminate\Support\Facades\Hash;
use Modules\Core\Models\Setting;

/**
 * Trait HasUserAttributes
 *
 * Gestiona accessors, mutators y atributos computados del modelo User.
 */
trait HasUserAttributes
{
    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }

    /**
     * Get image attribute (default avatar)
     */
    public function getImageAttribute(): string
    {
        return asset('images/default-user.png');
    }

    /**
     * Get the user's avatar URL
     * Alias for getImageAttribute for consistency with Customer model
     */
    public function getAvatarUrl(): string
    {
        // If user has user_img field set, use it
        if ($this->user_img) {
            return asset('storage/'.$this->user_img);
        }

        // Otherwise return default avatar
        return $this->getImageAttribute();
    }

    /**
     * Set password attribute - auto-hash if not already a valid hash.
     */
    public function setPasswordAttribute(string $password): void
    {
        // Only hash if the value is not already a hashed string (i.e. Hash::needsRehash
        // would return true for plain text). We detect plain text by checking whether
        // the value is a recognized hash format. If it already looks like a bcrypt/
        // argon hash, store it as-is (supports seeding pre-hashed values).
        $isAlreadyHashed = strlen($password) >= 60
            && preg_match('/^\$(?:2[ayb]|argon2(?:id?)?)\$/', $password);

        $this->attributes['password'] = $isAlreadyHashed ? $password : Hash::make($password);
    }

    /**
     * Get language code
     */
    public function getLanguageCode(): ?string
    {
        return $this->language ? $this->language->code : null;
    }

    /**
     * Get full language code (with region)
     */
    public function getLanguageCodeFull(): ?string
    {
        $region_code = $this->language->region_code
            ? strtoupper($this->language->region_code)
            : strtoupper($this->language->code);

        return $this->language ? ($this->language->code.'-'.$region_code) : null;
    }

    /**
     * Get user timezone
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * Display name formatted (respects locale)
     */
    public function displayName(): string
    {
        $lastNameFirst = get_localization_config('show_last_name_first', $this->getLanguageCode());

        if ($lastNameFirst) {
            return htmlspecialchars(trim($this->lastname.' '.$this->firstname));
        } else {
            return htmlspecialchars(trim($this->firstname.' '.$this->lastname));
        }
    }

    /**
     * Custom name + email for dropdowns
     */
    public function displayNameEmailOption(): string
    {
        return $this->displayName().'|||'.$this->email;
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    /**
     * Check if user account is temporarily locked (too many failed logins)
     */
    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until > now();
    }

    /**
     * Lock the user account for a given number of minutes
     */
    public function lockFor(int $minutes = 15): void
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Unlock the user account and reset failed login counter
     */
    public function unlock(): void
    {
        $this->update([
            'locked_until'       => null,
            'failed_login_count' => 0,
        ]);
    }

    /**
     * Increment failed login count and lock if threshold reached
     */
    public function incrementFailedLogins(int $maxAttempts = 5, int $lockMinutes = 15): void
    {
        $this->increment('failed_login_count');

        if ($this->failed_login_count >= $maxAttempts) {
            $this->lockFor($lockMinutes);
        }
    }

    /**
     * Enable customer
     */
    public function enable(): bool
    {
        $this->status = 'active';

        return $this->save();
    }

    /**
     * Disable customer
     */
    public function disable(): bool
    {
        $this->status = 'inactive';

        return $this->save();
    }

    /**
     * Get color scheme for UI
     */
    public function getColorScheme(): string
    {
        // Store mode support only sms theme
        if (config('app.store')) {
            return 'store';
        }

        if (! empty($this->color_scheme)) {
            return $this->color_scheme;
        } else {
            return Setting::get('frontend_scheme');
        }
    }

    /**
     * Get menu layout preference
     */
    public function getMenuLayout(): string
    {
        return $this->menu_layout == 'left' ? 'left' : 'top';
    }

    /**
     * Check if user has settings account
     */
    public function hasAdminAccount(): bool
    {
        return $this->user && $this->user->admin;
    }

    /**
     * Check if two-factor authentication is fully enabled and confirmed.
     * Requires both a secret and a confirmed_at timestamp.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return ! empty($this->two_factor_secret) && ! empty($this->two_factor_confirmed_at);
    }
}
