<?php

namespace Modules\Supplier\Models\Sync;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Supplier\Database\Factories\Sync\SyncSettingFactory;

class SyncSetting extends Model
{
    use HasFactory;

    protected $table = 'supplier_sync_settings';

    protected $fillable = ['key', 'value', 'type', 'label', 'description'];

    public $timestamps = true;

    protected static function newFactory(): SyncSettingFactory
    {
        return SyncSettingFactory::new();
    }

    /**
     * Get a setting value by key, with optional default.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'int' => (int) $setting->value,
            'bool' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, mixed $value, string $type = 'string'): void
    {
        $storedValue = match ($type) {
            'json' => json_encode($value),
            'bool' => $value ? '1' : '0',
            default => (string) $value,
        };

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue, 'type' => $type]
        );
    }
}
