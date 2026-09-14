<?php

namespace Modules\Forms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Estado de publicación de un formulario en la tienda.
 *
 * Guarda el hash del artefacto que la tienda tiene realmente servido, para poder
 * decir "hay cambios sin publicar" comparándolo con el que produce
 * [[FormArtifactBuilder]] ahora mismo, sin llamar a PrestaShop.
 */
class FormPrestashopPublication extends Model
{
    protected $table = 'form_prestashop_publications';

    protected $fillable = [
        'form_id',
        'form_key',
        'published_version',
        'published_hash',
        'published_at',
        'overrides_legacy',
        'published_by',
        'status',
        'last_error',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'published_version' => 'integer',
            'published_at' => 'datetime',
            'overrides_legacy' => 'boolean',
            'last_attempt_at' => 'datetime',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isLive(): bool
    {
        return $this->status === 'published' && $this->published_hash !== null;
    }

    /**
     * ¿Lo que hay en la tienda coincide con lo que hay aquí?
     */
    public function matches(string $currentHash): bool
    {
        return $this->isLive() && hash_equals((string) $this->published_hash, $currentHash);
    }

    public function markPublished(string $hash, ?int $userId = null): void
    {
        $this->forceFill([
            'status' => 'published',
            'published_hash' => $hash,
            'published_version' => $this->published_version + 1,
            'published_at' => now(),
            'published_by' => $userId ?? $this->published_by,
            'last_error' => null,
            'last_attempt_at' => now(),
        ])->save();
    }

    public function markFailed(string $error): void
    {
        $this->forceFill([
            'status' => 'failed',
            'last_error' => mb_substr($error, 0, 2000),
            'last_attempt_at' => now(),
        ])->save();
    }
}
