<?php

namespace Modules\Questions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una consulta de producto.
 */
class Question extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $connection = 'helpdesk';

    protected $table = 'product_questions';

    protected $guarded = ['id'];

    protected $casts = [
        'ps_question_id' => 'integer',
        'ps_product_id' => 'integer',
        'ps_lang_id' => 'integer',
        'ps_approved' => 'boolean',
        'ps_date' => 'datetime',
        'ps_date_upd' => 'datetime',
        'answered_at' => 'datetime',
        'moderated_at' => 'datetime',
        'translated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(QuestionTranslation::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(QuestionEvent::class)->latest('created_at');
    }

    /**
     * Sin respuesta no se publica: una pregunta suelta en la ficha solo enseña
     * que nadie la atendió.
     */
    public function isAnswered(): bool
    {
        return filled($this->answer);
    }

    public function isPublishable(): bool
    {
        return $this->isAnswered();
    }

    /** La tienda dice una cosa y el panel otra. */
    public function hasConflict(): bool
    {
        if ($this->status === self::STATUS_APPROVED) {
            return ! $this->ps_approved;
        }

        if ($this->status === self::STATUS_REJECTED) {
            return (bool) $this->ps_approved;
        }

        return false;
    }

    public function getModeratorNameAttribute(): ?string
    {
        if (! $this->moderated_by) {
            return null;
        }

        $user = User::find($this->moderated_by);

        return $user ? (trim(($user->firstname ?? '').' '.($user->lastname ?? '')) ?: $user->email) : null;
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAnswered($query)
    {
        return $query->whereNotNull('answer')->where('answer', '!=', '');
    }

    public function scopeUnanswered($query)
    {
        return $query->where(fn ($q) => $q->whereNull('answer')->orWhere('answer', ''));
    }

    public function scopeInConflict($query)
    {
        return $query->where(function ($q) {
            $q->where(fn ($q) => $q->where('status', self::STATUS_APPROVED)->where('ps_approved', false))
                ->orWhere(fn ($q) => $q->where('status', self::STATUS_REJECTED)->where('ps_approved', true));
        });
    }

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
