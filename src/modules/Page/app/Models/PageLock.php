<?php

namespace Modules\Page\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageLock extends Model
{
    protected $table = 'page_locks';

    protected $fillable = [
        'page_id',
        'user_id',
        'session_id',
        'locked_at',
        'expires_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener el lock activo de una página
     */
    public static function getActiveLock(Page $page): ?self
    {
        return static::where('page_id', $page->id)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Verificar si un usuario es el dueño del lock
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Verificar si el lock está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Renovar el lock (extender expiración)
     */
    public function renew(): self
    {
        $this->update([
            'expires_at' => now()->addMinutes(5),
        ]);

        return $this;
    }

    /**
     * Crear o renovar un lock
     */
    public static function acquireOrRenew(Page $page, User $user, ?string $sessionId = null): self
    {
        $existingLock = static::where('page_id', $page->id)->first();

        // Si existe lock del mismo usuario, renovar
        if ($existingLock && $existingLock->isOwnedBy($user) && ! $existingLock->isExpired()) {
            return $existingLock->renew();
        }

        // Si existe lock expirado o de otro usuario, eliminar
        if ($existingLock) {
            $existingLock->delete();
        }

        // Crear nuevo lock
        return static::create([
            'page_id' => $page->id,
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'locked_at' => now(),
            'expires_at' => now()->addMinutes(5),
        ]);
    }

    /**
     * Limpiar locks expirados
     */
    public static function cleanExpired(): int
    {
        return static::where('expires_at', '<', now())->delete();
    }
}
