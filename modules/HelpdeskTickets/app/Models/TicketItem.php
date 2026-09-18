<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Database\Factories\Helpdesk\TicketItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Concerns\BelongsToHelpdeskUser;

class TicketItem extends Model
{
    use BelongsToHelpdeskUser;

    /** @use HasFactory<TicketItemFactory> */
    use HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_items';

    protected $fillable = [
        'ticket_id',
        'author_id',
        'user_id',
        'type',
        'body',
        'html_body',
        'attachment_urls',
        'is_internal',
        'metadata',
        'sentiment',
        'sentiment_score',
        // Mismo par que helpdesk_conversation_items (HelpdeskTranslate): lo
        // que escribió el cliente, traducido al idioma del agente
        // (translated_body/source_locale), y lo que escribió el agente,
        // traducido al idioma del cliente antes de enviarse
        // (outgoing_translated_body/outgoing_target_locale).
        'translated_body',
        'source_locale',
        'outgoing_translated_body',
        'outgoing_target_locale',
    ];

    protected function casts(): array
    {
        return [
            'attachment_urls' => 'array',
            'metadata' => 'array',
            'is_internal' => 'boolean',
            'sentiment_score' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the ticket this item belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Get the customer who authored this message (if from customer)
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'author_id');
    }

    /**
     * Get the staff user who authored this message (if from agent)
     * Note: User model is on default mysql connection, not helpdesk
     */
    public function user()
    {
        // Create instance with explicit mysql connection for cross-database relationship

        return $this->belongsToHelpdeskUser('user_id', 'user');
    }

    /**
     * Get users who have read this message
     */
    public function reads(): HasMany
    {
        return $this->hasMany(TicketRead::class, 'ticket_item_id');
    }

    /**
     * Scope: Get only messages (not system events)
     */
    public function scopeMessages($query)
    {
        return $query->where('type', 'message');
    }

    /**
     * Scope: Get only system events
     */
    public function scopeEvents($query)
    {
        return $query->where('type', '!=', 'message');
    }

    /**
     * Scope: Get only internal notes
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope: Get only external messages
     */
    public function scopeExternal($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope: Get messages from customers
     */
    public function scopeFromCustomer($query)
    {
        return $query->whereNotNull('author_id')->whereNull('user_id');
    }

    /**
     * Scope: Get messages from agents
     */
    public function scopeFromAgent($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Check if this is a message (not a system event)
     */
    public function isMessage(): bool
    {
        return $this->type === 'message';
    }

    /**
     * Check if this is a system event
     */
    public function isEvent(): bool
    {
        return $this->type !== 'message';
    }

    /**
     * Check if message is from a customer
     */
    public function isFromCustomer(): bool
    {
        return $this->author_id !== null && $this->user_id === null;
    }

    /**
     * Check if message is from an agent
     */
    public function isFromAgent(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Get readable event type label
     */
    public function getEventLabelAttribute(): string
    {
        $labels = [
            'message' => 'Mensaje',
            'internal_note' => 'Nota Interna',
            'status_change' => 'Cambio de Estado',
            'assigned' => 'Asignado',
            'unassigned' => 'Desasignado',
            'priority_changed' => 'Prioridad Cambiada',
            'category_changed' => 'Categoría Cambiada',
            'sla_warning' => 'Advertencia SLA',
            'sla_breach' => 'Incumplimiento SLA',
            'attachment_added' => 'Adjunto Añadido',
            'customer_replied' => 'Respuesta del Cliente',
            'closed' => 'Cerrado',
            'reopened' => 'Reabierto',
        ];

        return $labels[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    /**
     * Get badge color for event type
     */
    public function getEventColorAttribute(): string
    {
        $colors = [
            'message' => 'primary',
            'internal_note' => 'secondary',
            'status_change' => 'info',
            'assigned' => 'success',
            'unassigned' => 'warning',
            'priority_changed' => 'warning',
            'category_changed' => 'info',
            'sla_warning' => 'warning',
            'sla_breach' => 'danger',
            'attachment_added' => 'info',
            'customer_replied' => 'primary',
            'closed' => 'danger',
            'reopened' => 'success',
        ];

        return $colors[$this->type] ?? 'secondary';
    }

    /**
     * Get the sender's name (customer or agent)
     */
    public function getSenderNameAttribute(): string
    {
        if ($this->isFromCustomer()) {
            // El cliente (Modules\Helpdesk\Models\Customer) sí tiene 'name'.
            return $this->author?->name ?: ($this->author?->email ?: 'Desconocido');
        }

        if ($this->isFromAgent()) {
            // fullName(), no ->name: el modelo User de esta app guarda
            // firstname/lastname y no tiene columna 'name', así que ->name era
            // siempre null y TODOS los mensajes de agente del hilo salían
            // como el literal "Agente" en vez del nombre de quien escribió.
            return $this->user?->fullName() ?: ($this->user?->email ?: 'Agente');
        }

        return 'Sistema';
    }

    /**
     * Get the sender's avatar URL
     */
    public function getSenderAvatarAttribute(): ?string
    {
        if ($this->isFromCustomer()) {
            return $this->author?->getAvatarUrl();
        }

        if ($this->isFromAgent()) {
            return $this->user?->getAvatarUrl();
        }

        return null;
    }

    /**
     * Mark message as read by a user
     */
    public function markAsRead($userId)
    {
        return $this->reads()->firstOrCreate([
            'user_id' => $userId,
        ]);
    }

    /**
     * Get read count for this message
     */
    public function getReadCount(): int
    {
        return $this->reads()->distinct('user_id')->count();
    }

    /**
     * Check if read by a specific user
     */
    public function isReadByUser($userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }

    /**
     * Get list of users who have read this message
     */
    public function getReadByUsers()
    {
        $userIds = $this->reads()
            ->select('user_id')
            ->distinct()
            ->pluck('user_id');

        return User::whereIn('id', $userIds)->get();
    }

    /**
     * Get body content (prefer HTML over plain text)
     */
    public function getContentAttribute(): ?string
    {
        return $this->html_body ?? $this->body;
    }

    /**
     * Get plain text version of body
     */
    public function getPlainTextAttribute(): ?string
    {
        if ($this->html_body) {
            return strip_tags($this->html_body);
        }

        return $this->body;
    }

    /**
     * html_body de un item de tipo "message" puede venir de un correo
     * ENTRANTE (controlado por quien escriba al ticket) — bug de seguridad
     * real encontrado en QA (ago-2026): agents/tickets/show.blade.php
     * llamaba a clean_html($item->html_body), una función global que no
     * está cargada (no figura en composer.json autoload.files pese al
     * comentario que dice lo contrario — confirmado con function_exists()),
     * así que la vista clásica del agente reventaba con "Call to undefined
     * function clean_html()" en cualquier ticket con html_body real. Se
     * reutiliza el mismo purificador ya centralizado en TicketMail (mismo
     * origen de riesgo: HTML de un correo entrante).
     */
    public function safeHtmlBody(): string
    {
        return TicketMail::purifyHtml($this->html_body);
    }

    /**
     * Nombre legible del idioma detectado en source_locale (ej. 'en' ->
     * 'inglés'). TranslateIncomingTicketMessage ya calcula y guarda
     * translated_body/source_locale, pero ninguna vista del panel los
     * mostraba -- el agente nunca veía la traducción, solo el mensaje
     * original tal cual llegó (detectado 3-sep-2026 probando el flujo real).
     * Sin ext-intl en este contenedor (Locale::getDisplayLanguage no
     * disponible), de ahí el mapeo manual acotado a los idiomas reales que
     * maneja HelpdeskTranslate/DeepL.
     */
    public function getSourceLanguageNameAttribute(): ?string
    {
        if (! $this->source_locale) {
            return null;
        }

        $names = [
            'es' => 'español', 'en' => 'inglés', 'fr' => 'francés',
            'de' => 'alemán', 'it' => 'italiano', 'pt' => 'portugués',
            'ca' => 'catalán', 'eu' => 'euskera', 'gl' => 'gallego',
            'nl' => 'neerlandés', 'ru' => 'ruso', 'zh' => 'chino',
            'ja' => 'japonés', 'ar' => 'árabe', 'pl' => 'polaco',
        ];

        $code = strtolower(substr($this->source_locale, 0, 2));

        return $names[$code] ?? strtoupper($code);
    }

    /**
     * Check if message has attachments
     */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachment_urls) && count($this->attachment_urls) > 0;
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCountAttribute(): int
    {
        return $this->hasAttachments() ? count($this->attachment_urls) : 0;
    }
}
