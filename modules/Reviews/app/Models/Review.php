<?php

namespace Modules\Reviews\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una opinión de producto, tal y como llegó de la tienda.
 */
class Review extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const ENTITY_PRODUCT = 'product';

    public const ENTITY_STORE = 'store';

    public const ORIGIN_CUSTOMER = 'customer';

    public const ORIGIN_GOOGLE = 'google';

    /** Escala real de la columna `stars` en PrestaShop. */
    public const STARS_MAX = 10;

    protected $connection = 'helpdesk';

    protected $table = 'product_reviews';

    protected $guarded = ['id'];

    protected $casts = [
        'source_id' => 'integer',
        'ps_comment_id' => 'integer',
        'ps_product_id' => 'integer',
        'ps_customer_id' => 'integer',
        'ps_order_id' => 'integer',
        'ps_lang_id' => 'integer',
        'stars' => 'integer',
        'ps_active' => 'boolean',
        'ps_date' => 'datetime',
        'ps_date_upd' => 'datetime',
        'moderated_at' => 'datetime',
        'translated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(ReviewTranslation::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ReviewEvent::class)->latest('created_at');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ReviewSource::class, 'source_id');
    }

    public function isFromGoogle(): bool
    {
        return $this->origin === self::ORIGIN_GOOGLE;
    }

    /**
     * Una reseña de Google no se toca: no se traduce ni se edita. Es lo que
     * alguien escribió en su ficha, y reescribirlo sería falsearlo.
     */
    public function isEditable(): bool
    {
        return ! $this->isFromGoogle();
    }

    public function scopeFromGoogle($query)
    {
        return $query->where('origin', self::ORIGIN_GOOGLE);
    }

    public function scopeFromCustomers($query)
    {
        return $query->where('origin', self::ORIGIN_CUSTOMER);
    }

    /** Valoración en la escala que ve el cliente. */
    public function getRatingAttribute(): float
    {
        return round($this->stars / 2, 1);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * La tienda dice una cosa y el panel otra: alguien moderó por el otro lado.
     *
     * Solo tiene sentido para lo que vive en PrestaShop. Una reseña de Google no
     * está allí, así que su `ps_active` no significa nada: darlo por discrepancia
     * marcaba como conflicto las 30 que simplemente se habían aprobado aquí.
     */
    public function hasConflict(): bool
    {
        if ($this->isFromGoogle()) {
            return false;
        }

        if ($this->status === self::STATUS_APPROVED) {
            return ! $this->ps_active;
        }

        if ($this->status === self::STATUS_REJECTED) {
            return (bool) $this->ps_active;
        }

        return false;
    }

    /**
     * Las que discrepan con la tienda. Query equivalente a hasConflict(), para
     * poder filtrarlas y contarlas sin traerlas todas a memoria.
     */
    public function scopeInConflict($query)
    {
        return $query->where('origin', '!=', self::ORIGIN_GOOGLE)
            ->where(function ($q) {
                $q->where(fn ($q) => $q->where('status', self::STATUS_APPROVED)->where('ps_active', false))
                    ->orWhere(fn ($q) => $q->where('status', self::STATUS_REJECTED)->where('ps_active', true));
            });
    }

    /**
     * Quién tomó la decisión. El User del panel no tiene `name`: guarda
     * firstname y lastname por separado.
     */
    public function getModeratorNameAttribute(): ?string
    {
        if (! $this->moderated_by) {
            return null;
        }

        $user = User::find($this->moderated_by);

        if (! $user) {
            return null;
        }

        return trim(($user->firstname ?? '').' '.($user->lastname ?? '')) ?: ($user->email ?? null);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Deja constancia de un cambio. Todo lo que altera una opinión pasa por
     * aquí: con dos paneles moderando, sin rastro no hay forma de distinguir un
     * cambio legítimo de un fallo de sincronización.
     */
    public function recordEvent(string $event, string $source, array $data = [], ?int $actorId = null): void
    {
        $this->events()->create([
            'event' => $event,
            'source' => $source,
            'actor_id' => $actorId,
            'data' => $data ?: null,
            'created_at' => now(),
        ]);
    }
}
