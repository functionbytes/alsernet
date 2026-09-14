<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HelpdeskTickets\Models\Concerns\BelongsToHelpdeskUser;

/**
 * Regla de bloqueo de remitentes para la ingesta de email de HelpdeskTickets
 * (FetchTicketEmailsJob). Independiente de Customer::banned_at/is_blocked
 * (core Helpdesk): esa columna banea a un cliente ya existente desde el
 * inbox de Conversaciones; esta tabla bloquea ANTES de que exista Customer,
 * por email exacto o por dominio completo (incluye subdominios).
 */
class TicketEmailBlacklist extends Model
{
    use BelongsToHelpdeskUser;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_email_blacklist';

    protected $fillable = [
        'type',
        'value',
        'reason',
        'is_active',
        'added_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'matched_count' => 'integer',
            'last_matched_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $entry) {
            $entry->value = strtolower(trim($entry->value));
        });

        // No hay FK real a nivel de BD (mismo patrón que el resto del
        // módulo: columnas indexadas, sin constraint) — se cascada a mano
        // para no dejar hits huérfanos al borrar una regla.
        static::deleting(function (self $entry) {
            $entry->hits()->delete();
        });
    }

    /**
     * User model vive en la conexión por defecto, no en 'helpdesk' — mismo
     * patrón que TicketMailView::user().
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsToHelpdeskUser('added_by', 'addedBy');
    }

    public function hits(): HasMany
    {
        return $this->hasMany(TicketEmailBlacklistHit::class, 'blacklist_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Busca la primera regla activa que bloquee $email, por coincidencia
     * exacta de email o por dominio (dominio exacto o subdominio). Devuelve
     * null si el remitente no está en la lista negra.
     */
    public static function matches(string $email): ?self
    {
        $email = strtolower(trim($email));

        if ($email === '' || ! str_contains($email, '@')) {
            return null;
        }

        $domain = substr(strrchr($email, '@'), 1);

        $rule = static::active()->where('type', 'email')->where('value', $email)->first();
        if ($rule) {
            return $rule;
        }

        return static::active()->where('type', 'domain')
            ->where(function (Builder $query) use ($domain) {
                $query->where('value', $domain)
                    ->orWhereRaw('? LIKE CONCAT(\'%.\', value)', [$domain]);
            })
            ->first();
    }

    public function registerMatch(string $fromEmail, ?string $subject = null, ?string $bodyHtml = null, ?string $bodyText = null): void
    {
        $this->increment('matched_count', 1, ['last_matched_at' => now()]);

        $this->hits()->create([
            'from_email' => $fromEmail,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        ]);
    }
}
