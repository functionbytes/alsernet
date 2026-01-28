<?php

namespace Modules\Mailing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Mailing\Traits\HasUid;

class GeneralSetting extends Model
{
    use HasFactory, HasUid;

    protected $table = 'mailing_general_settings';

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'user_id',
        'key',
        'setting_value',
        'options',
        'description',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'setting_value' => 'json',
            'options' => 'json',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this setting.
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
     * Get a specific setting value by key.
     */
    public static function getSetting(int $userId, string $key, mixed $default = null): mixed
    {
        $setting = static::where('user_id', $userId)
            ->where('key', $key)
            ->first();

        return $setting?->setting_value ?? $default;
    }

    /**
     * Set a setting value for a user.
     */
    public static function setSetting(int $userId, string $key, mixed $value, ?array $options = null): static
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'key' => $key,
            ],
            [
                'setting_value' => $value,
                'options' => $options,
            ]
        );
    }

    /**
     * Get all settings for a user.
     */
    public static function getAll(int $userId): array
    {
        return static::where('user_id', $userId)
            ->get()
            ->pluck('setting_value', 'key')
            ->toArray();
    }
}
