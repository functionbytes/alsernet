<?php

namespace Modules\Page\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageAutoSave extends Model
{
    protected $table = 'page_auto_saves';

    protected $fillable = [
        'page_id',
        'user_id',
        'data',
        'status',
        'content',
        'saved_at',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'json',
        'saved_at' => 'datetime',
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
     * Obtener el borrador más reciente de una página para un usuario
     */
    public static function getLatestDraft(Page $page, User $user): ?self
    {
        return static::where('page_id', $page->id)
            ->where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->latest('saved_at')
            ->first();
    }

    /**
     * Limpiar borradores expirados
     */
    public static function cleanExpired(): int
    {
        return static::where('expires_at', '<', now())
            ->orWhereNull('expires_at')
            ->delete();
    }

    /**
     * Crear o actualizar un borrador automático
     */
    public static function createOrUpdateDraft(Page $page, User $user, array $data): self
    {
        $draft = static::where('page_id', $page->id)
            ->where('user_id', $user->id)
            ->latest('saved_at')
            ->first();

        if ($draft && $draft->expires_at && $draft->expires_at->isFuture()) {
            $draft->update([
                'data' => $data,
                'content' => $data['content'] ?? null,
                'status' => $data['status'] ?? $page->status,
                'saved_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);

            return $draft;
        }

        return static::create([
            'page_id' => $page->id,
            'user_id' => $user->id,
            'data' => $data,
            'content' => $data['content'] ?? null,
            'status' => $data['status'] ?? $page->status,
            'saved_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);
    }
}
