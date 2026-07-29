<?php

namespace Modules\Supplier\Models\Automation;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Supplier\Database\Factories\Automation\AutomationSettingFactory;

/**
 * AutomationSetting Model
 *
 * Global configuration backups for the automation system.
 *
 * @property int $id
 * @property string $key Configuration key (unique)
 * @property string $value Configuration value (can be JSON)
 * @property string $type Type: string, integer, boolean, json, encrypted
 * @property string $category Category: connection, security, limits, defaults
 * @property string|null $description Description for UI
 * @property bool $is_sensitive Hide in logs if sensitive
 * @property int|null $updated_by FK to users
 * @property Carbon|null $updated_at
 * @property-read User|null $updater
 */
class AutomationSetting extends Model
{
    use HasFactory;

    protected $table = 'supplier_automation_settings';

    public const UPDATED_AT = 'updated_at';

    public const CREATED_AT = null;

    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'description',
        'is_sensitive',
        'updated_by',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory(): AutomationSettingFactory
    {
        return AutomationSettingFactory::new();
    }

    /**
     * Relationship to user who updated the setting
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope: Filter by category
     */
    public function scopeOfCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter sensitive backups
     */
    public function scopeSensitive(Builder $query, bool $sensitive = true): Builder
    {
        return $query->where('is_sensitive', $sensitive);
    }

    /**
     * Get the typed value
     */
    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            'encrypted' => decrypt($this->value),
            default => $this->value,
        };
    }

    /**
     * Set a typed value
     */
    public function setTypedValue(mixed $value): void
    {
        $this->value = match ($this->type) {
            'json' => json_encode($value),
            'encrypted' => encrypt($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        return $setting ? $setting->getTypedValue() : $default;
    }

    /**
     * Set a setting value by key
     */
    public static function setValue(string $key, mixed $value, ?int $userId = null): self
    {
        $setting = self::firstOrNew(['key' => $key]);
        $setting->setTypedValue($value);
        $setting->updated_by = $userId;
        $setting->save();

        return $setting;
    }

    /**
     * Check if setting exists
     */
    public static function has(string $key): bool
    {
        return self::where('key', $key)->exists();
    }

    /**
     * Get all backups as key-value array
     */
    public static function getAllSettings(?string $category = null): array
    {
        $query = self::query();

        if ($category) {
            $query->where('category', $category);
        }

        return $query->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->getTypedValue()];
        })->toArray();
    }
}
