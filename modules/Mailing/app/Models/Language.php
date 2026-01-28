<?php

namespace Modules\Mailing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Mailing\Traits\HasUid;

class Language extends Model
{
    use HasFactory, HasUid;

    protected $table = 'mailing_languages';

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'user_id',
        'code',
        'name',
        'native_name',
        'locale',
        'translations',
        'settings',
        'is_default',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'translations' => 'json',
            'settings' => 'json',
            'is_default' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this language.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get translations as an array.
     */
    public function getTranslationsArray(): array
    {
        if (empty($this->translations)) {
            return [];
        }

        return is_array($this->translations) ? $this->translations : json_decode($this->translations, true) ?? [];
    }

    /**
     * Get settings as an array.
     */
    public function getSettingsArray(): array
    {
        if (empty($this->settings)) {
            return [];
        }

        return is_array($this->settings) ? $this->settings : json_decode($this->settings, true) ?? [];
    }

    /**
     * Get a translation by key.
     */
    public function getTranslation(string $key, ?string $default = null): ?string
    {
        $translations = $this->getTranslationsArray();

        // Support dot notation for nested translations
        $keys = explode('.', $key);
        $value = $translations;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return is_string($value) ? $value : $default;
    }

    /**
     * Set a translation value.
     */
    public function setTranslation(string $key, string $value): void
    {
        $translations = $this->getTranslationsArray();

        // Support dot notation for nested translations
        $keys = explode('.', $key);
        $current = &$translations;

        foreach ($keys as $k) {
            if (! isset($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
        $this->translations = $translations;
    }

    /**
     * Get all translations.
     */
    public function getAllTranslations(): array
    {
        return $this->getTranslationsArray();
    }

    /**
     * Set multiple translations at once.
     */
    public function setTranslations(array $translations): void
    {
        $this->translations = array_merge($this->getTranslationsArray(), $translations);
    }

    /**
     * Get a specific setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->getSettingsArray();

        return $settings[$key] ?? $default;
    }

    /**
     * Set a specific setting value.
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->getSettingsArray();
        $settings[$key] = $value;
        $this->settings = $settings;
    }

    /**
     * Check if this is the default language.
     */
    public function isDefault(): bool
    {
        return $this->is_default ?? false;
    }

    /**
     * Set this language as the default.
     */
    public function setAsDefault(): void
    {
        // Reset other default languages for this user
        static::query()
            ->where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this as default
        $this->update(['is_default' => true]);
    }

    /**
     * Get the display name for this language.
     */
    public function getDisplayName(): string
    {
        return $this->native_name ?? $this->name ?? $this->code;
    }

    /**
     * Get the full locale code.
     */
    public function getLocaleCode(): string
    {
        return $this->locale ?? $this->code;
    }

    /**
     * Check if a translation key exists.
     */
    public function hasTranslation(string $key): bool
    {
        $translations = $this->getTranslationsArray();
        $keys = explode('.', $key);
        $value = $translations;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove a translation by key.
     */
    public function removeTranslation(string $key): void
    {
        $translations = $this->getTranslationsArray();
        $keys = explode('.', $key);

        if (empty($keys)) {
            return;
        }

        $current = &$translations;

        for ($i = 0; $i < count($keys) - 1; $i++) {
            $k = $keys[$i];
            if (isset($current[$k]) && is_array($current[$k])) {
                $current = &$current[$k];
            } else {
                return;
            }
        }

        $lastKey = end($keys);
        unset($current[$lastKey]);

        $this->translations = $translations;
    }

    /**
     * Get count of translations.
     */
    public function getTranslationCount(): int
    {
        return count($this->getTranslationsArray());
    }
}
