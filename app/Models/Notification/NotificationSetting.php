<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'channel',
        'notification_type',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verificar si un canal está habilitado para un tipo de notificación
     */
    public static function isEnabled(int $userId, string $channel, string $notificationType): bool
    {
        $setting = static::where('user_id', $userId)
            ->where('channel', $channel)
            ->where('notification_type', $notificationType)
            ->first();

        return $setting ? $setting->enabled : true; // Por defecto habilitado
    }

    /**
     * Obtener configuraciones por usuario
     */
    public static function getSettingsForUser(int $userId): array
    {
        return static::where('user_id', $userId)->get()->groupBy('notification_type')->toArray();
    }
}
