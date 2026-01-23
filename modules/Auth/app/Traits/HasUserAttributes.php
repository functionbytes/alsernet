<?php

namespace Modules\Auth\Traits;

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
     * Set password attribute - auto-hash if not already hashed
     */
    public function setPasswordAttribute($password): void
    {
        if (strlen($password) !== 60 || ! preg_match('/^\$2y\$/', $password)) {
            $this->attributes['password'] = bcrypt($password);
        } else {
            $this->attributes['password'] = $password;
        }
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
            return \Modules\Core\Models\Setting::get('frontend_scheme');
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
     * Check if user has admin account
     */
    public function hasAdminAccount(): bool
    {
        return $this->user && $this->user->admin;
    }
}
