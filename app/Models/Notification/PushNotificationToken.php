<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotificationToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'device_id',
        'active',
        'last_used_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marcar token como usado
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Desactivar token
     */
    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    /**
     * Obtener tokens activos para un usuario
     */
    public static function getActiveTokensForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)
            ->where('active', true)
            ->get();
    }
}
