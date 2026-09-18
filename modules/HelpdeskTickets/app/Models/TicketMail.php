<?php

namespace Modules\HelpdeskTickets\Models;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Models\Concerns\BelongsToHelpdeskUser;

class TicketMail extends Model
{
    use BelongsToHelpdeskUser, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_mails';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'category_id',
        'tags',
        'ticket_comment_id',
        'ticket_item_id',
        'direction',
        'is_internal',
        'message_id',
        'in_reply_to',
        'references',
        'from',
        'to',
        'cc',
        'bcc',
        'subject',
        'body_html',
        'body_text',
        'attachments',
        'headers',
        'status',
        'delivery_error',
        'sent_at',
        'delivered_at',
        'scheduled_at',
        'cancel_if_customer_replies',
        'raw_email',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'headers' => 'array',
            'tags' => 'array',
            'is_internal' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'cancel_if_customer_replies' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    // ────────────────────────────────────────────────────────────────
    // Relationships
    // ────────────────────────────────────────────────────────────────

    /**
     * Get the ticket this mail belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the associated comment (if any)
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class, 'ticket_comment_id');
    }

    /**
     * Agent who sent this mail. Sin FK real: users vive en la conexión por
     * defecto (mariadb), no en 'helpdesk' — mismo patrón que Ticket::assignee().
     */
    public function user(): BelongsTo
    {
        return $this->belongsToHelpdeskUser('user_id', 'user');
    }

    /**
     * Category of this mail (independent from the ticket's own category).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    // ────────────────────────────────────────────────────────────────
    // Query Scopes
    // ────────────────────────────────────────────────────────────────

    /**
     * Get only inbound emails
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    /**
     * Get only outbound emails
     */
    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    /**
     * Get emails by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get pending emails
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get sent emails
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Get delivered emails
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Get bounced emails
     */
    public function scopeBounced($query)
    {
        return $query->where('status', 'bounced');
    }

    /**
     * Get failed emails
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get emails scheduled for future delivery (not yet sent).
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')->whereNotNull('scheduled_at');
    }

    /**
     * Get scheduled emails due for delivery now (consumed by the scheduled
     * send command).
     */
    public function scopeDueForDelivery($query)
    {
        return $query->scheduled()->where('scheduled_at', '<=', now());
    }

    /**
     * Avisos internos (p.ej. "Escalado a nivel 2") — nunca visibles para el
     * cliente. Se marca explícitamente al componer, no se infiere del dominio
     * del destinatario.
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Get emails ordered by newest first
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get emails ordered by oldest first
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    // ────────────────────────────────────────────────────────────────
    // Email Threading Methods
    // ────────────────────────────────────────────────────────────────

    /**
     * Check if this email is a reply to another email
     */
    public function isReply(): bool
    {
        return $this->in_reply_to !== null || $this->references !== null;
    }

    // ────────────────────────────────────────────────────────────────
    // Status Management
    // ────────────────────────────────────────────────────────────────

    /**
     * Mark email as scheduled for future delivery.
     */
    public function markAsScheduled(\DateTimeInterface $when): void
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $when,
        ]);
    }

    /**
     * Mark email as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark email as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark email as bounced
     */
    public function markAsBounced(string $error): void
    {
        $this->update([
            'status' => 'bounced',
            'delivery_error' => $error,
        ]);
    }

    /**
     * Mark email as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'delivery_error' => $error,
        ]);
    }

    /**
     * Check if email was successfully delivered
     */
    public function wasDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Check if email failed
     */
    public function hasFailed(): bool
    {
        return in_array($this->status, ['bounced', 'failed']);
    }

    // ────────────────────────────────────────────────────────────────
    // Accessors
    // ────────────────────────────────────────────────────────────────

    /**
     * Get attachment count
     */
    public function getAttachmentCountAttribute(): int
    {
        return count($this->attachments ?? []);
    }

    /**
     * Check if email has attachments
     */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachments);
    }

    /**
     * Get email recipients (to + cc + bcc)
     */
    public function getRecipientsAttribute(): array
    {
        $recipients = [$this->to];

        if ($this->cc) {
            $recipients = array_merge($recipients, explode(',', $this->cc));
        }

        if ($this->bcc) {
            $recipients = array_merge($recipients, explode(',', $this->bcc));
        }

        return array_filter(array_map('trim', $recipients));
    }

    /**
     * Get preferred body (HTML or plain text)
     */
    public function getBodyAttribute(): string
    {
        return $this->body_html ?? $this->body_text ?? '';
    }

    /**
     * Get plain text version
     */
    public function getPlainTextAttribute(): string
    {
        if ($this->body_text) {
            return $this->body_text;
        }

        if ($this->body_html) {
            return strip_tags($this->body_html);
        }

        return '';
    }

    /**
     * Get status label in Spanish
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'scheduled' => 'Programado',
            'sent' => 'Enviado',
            'delivered' => 'Entregado',
            'bounced' => 'Rebotado',
            'failed' => 'Falló',
            'received' => 'Recibido',
            default => $this->status,
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'scheduled' => 'warning',
            'sent' => 'info',
            'delivered' => 'success',
            'bounced' => 'danger',
            'failed' => 'danger',
            'received' => 'info',
            default => 'secondary',
        };
    }

    // ────────────────────────────────────────────────────────────────
    // Presentation (bandeja "Emails enviados")
    // ────────────────────────────────────────────────────────────────

    /**
     * Forma de fila para la bandeja global de emails — usado tanto por la
     * hidratación SSR inicial (index.blade.php) como por el JSON de
     * TicketMailsController::index()/data() cuando se filtra/pagina por
     * AJAX. Vive aquí (no duplicado en el controller y en la vista) porque
     * ya se detectaron dos inconsistencias reales por tener el mapeo en dos
     * sitios (status_pill vs status_color, origin ya traducido vs slug
     * crudo) — un único punto de verdad evita que se repita.
     *
     * @return array<string, mixed>
     */
    public function toListRow(): array
    {
        $ticket = $this->ticket;

        return [
            'id' => $this->id,
            'ticket_id' => $ticket?->id,
            'ticket_number' => $ticket?->ticket_number,
            'subject' => $this->subject,
            'to' => $this->to,
            'snippet' => Str::limit(strip_tags($this->body_text ?: $this->body_html ?: ''), 140),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'direction' => $this->direction,
            'is_internal' => (bool) $this->is_internal,
            'origin' => $ticket?->source,
            'category' => $this->category?->name,
            'agent' => $this->user ? trim("{$this->user->firstname} {$this->user->lastname}") : null,
            'tags' => $this->tags ?? [],
            'customer_name' => $ticket?->customer?->name,
            'initials' => self::initialsFor($ticket?->customer?->name ?? $this->to),
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'time_short' => ($this->sent_at ?? $this->created_at)?->format('H:i'),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'has_attachments' => $this->hasAttachments(),
            'url_data' => route('manager.helpdesk.tickets.emails.data', $this),
            'url_resend' => route('manager.helpdesk.tickets.emails.resend', $this),
            'url_summary' => route('manager.helpdesk.tickets.emails.summary', $this),
            'url_translate' => route('manager.helpdesk.tickets.emails.translate', $this),
            'url_tags' => route('manager.helpdesk.tickets.emails.tags', $this),
        ];
    }

    private static ?HTMLPurifier $htmlPurifier = null;

    /**
     * body_html crudo, sin sanitizar, es contenido de un correo ENTRANTE —
     * cualquiera que le escriba a la dirección de soporte controla ese HTML.
     * Bug de seguridad real encontrado en QA (ago-2026): tanto
     * TicketsCrudController::data() como TicketMailsController::show()
     * exponían $mail->body_html tal cual en el JSON, y tickets-app.js lo
     * inyectaba directo vía jQuery .html() (renderMailPane) — un email con
     * `<img src=x onerror="...">` habría ejecutado JS en la sesión del
     * agente al abrir el ticket (XSS almacenado). clean_html()/clean() del
     * Core no están disponibles (no cargados vía composer autoload.files,
     * verificado con function_exists()), así que se sanea aquí mismo con
     * HTMLPurifier directo — mismo criterio de allowlist conservador que
     * Modules\Supplier\Helpers\HtmlSanitizer::clean() y Core\Helper::clean_html().
     */
    public function safeBodyHtml(): string
    {
        return self::purifyHtml($this->body_html);
    }

    /**
     * Veredicto antispam del correo, leído de las cabeceras que archivó
     * FetchTicketEmailsJob::extractHeaders() — es la puntuación REAL que puso
     * el filtro del servidor entrante (SpamAssassin, Rspamd…), no una
     * calculada aquí. Devuelve null cuando el correo no pasó por ningún
     * filtro o el filtro no dejó ninguna de las cuatro cabeceras: en ese
     * caso el detalle no pinta el chip, en vez de enseñar un 0 que
     * significaría "limpio" sin que nadie lo haya comprobado.
     *
     * @return array{score: float, threshold: float, is_spam: bool}|null
     */
    public function spamVerdict(): ?array
    {
        $headers = $this->headers ?? [];
        if (! is_array($headers) || $headers === []) {
            return null;
        }

        // Las cabeceras se archivan con su nombre original ("X-Spam-Score");
        // se indexa en minúsculas para no depender de cómo las escribió el
        // servidor de correo, que varía entre implementaciones.
        $lower = [];
        foreach ($headers as $name => $value) {
            $lower[strtolower((string) $name)] = is_string($value) ? trim($value) : $value;
        }

        $score = null;
        $threshold = null;

        // 1) X-Spam-Score: "1.2" o "-0.8" a secas.
        if (isset($lower['x-spam-score']) && is_numeric($lower['x-spam-score'])) {
            $score = (float) $lower['x-spam-score'];
        }

        // 2) X-Spam-Status: "No, score=1.2 required=5.0 tests=..." — el
        // formato de SpamAssassin, que además trae el umbral configurado.
        if (isset($lower['x-spam-status']) && is_string($lower['x-spam-status'])) {
            if ($score === null && preg_match('/score=(-?\d+(?:\.\d+)?)/i', $lower['x-spam-status'], $m)) {
                $score = (float) $m[1];
            }
            if (preg_match('/required=(-?\d+(?:\.\d+)?)/i', $lower['x-spam-status'], $m)) {
                $threshold = (float) $m[1];
            }
        }

        // 3) X-Spam-Level: una fila de asteriscos, uno por punto entero.
        // Es el último recurso: solo da la parte entera de la puntuación.
        if ($score === null && isset($lower['x-spam-level']) && is_string($lower['x-spam-level'])) {
            $stars = substr_count($lower['x-spam-level'], '*');
            if ($stars > 0) {
                $score = (float) $stars;
            }
        }

        if ($score === null) {
            return null;
        }

        $threshold ??= 5.0;

        return [
            'score' => $score,
            'threshold' => $threshold,
            'is_spam' => $score >= $threshold
                || strtolower((string) ($lower['x-spam-flag'] ?? '')) === 'yes',
        ];
    }

    public static function purifyHtml(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        if (self::$htmlPurifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,s,del,ins,a[href|title],ul,ol,li,blockquote,pre,code,'
                .'h1,h2,h3,h4,h5,h6,img[src|alt|title|width|height],table,thead,tbody,tr,th,td,span[style],div,hr,sub,sup');
            $config->set('HTML.TargetBlank', true);
            $config->set('HTML.Nofollow', true);
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
            $config->set('CSS.AllowedProperties', ['color', 'background-color', 'font-weight', 'font-style', 'text-decoration', 'text-align']);
            $config->set('AutoFormat.AutoParagraph', false);
            $config->set('AutoFormat.RemoveEmpty', false);
            $config->set('Cache.DefinitionImpl', null);

            self::$htmlPurifier = new HTMLPurifier($config);
        }

        return self::$htmlPurifier->purify($html);
    }

    /**
     * Iniciales para el avatar de la fila (2 letras a partir del nombre del
     * cliente, o del destinatario si no hay cliente asociado).
     */
    public static function initialsFor(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return '—';
        }

        $parts = preg_split('/\s+/', $name);
        $letters = mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1] ?? '', 0, 1));

        return $letters ?: '—';
    }

    // ────────────────────────────────────────────────────────────────
    // Static Factory Methods
    // ────────────────────────────────────────────────────────────────

    /**
     * Create a mail record from inbound email data
     */
    public static function createFromInbound(array $data, Ticket $ticket): self
    {
        return static::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'message_id' => $data['message_id'] ?? Str::uuid(),
            'in_reply_to' => $data['in_reply_to'] ?? null,
            'references' => $data['references'] ?? null,
            'from' => $data['from'],
            'to' => $data['to'],
            'cc' => $data['cc'] ?? null,
            'bcc' => $data['bcc'] ?? null,
            'subject' => $data['subject'],
            'body_html' => $data['body_html'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'attachments' => $data['attachments'] ?? null,
            'headers' => $data['headers'] ?? null,
            'raw_email' => $data['raw_email'] ?? null,
            'status' => 'received',
        ]);
    }

    /**
     * Create outbound mail record
     */
    public static function createOutbound(
        Ticket $ticket,
        string $from,
        string $to,
        string $subject,
        string $body,
        ?string $bodyHtml = null,
        ?array $cc = null,
        ?array $bcc = null
    ): self {
        return static::create([
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
            // Sin '<' '>' — los correos entrantes se guardan sin corchetes
            // (así los normaliza webklex/php-imap al parsear Message-ID/
            // In-Reply-To/References); si esto se guardara con corchetes, la
            // respuesta del cliente a este correo nunca engancharía por
            // comparación exacta de string contra este message_id (ver
            // findOrCreateTicket()). Los corchetes solo hacen falta en el
            // header real del correo, que Symfony/addIdHeader() ya agrega.
            'message_id' => Str::uuid().'@'.config('app.name'),
            'from' => $from,
            'to' => $to,
            'cc' => $cc ? implode(',', $cc) : null,
            'bcc' => $bcc ? implode(',', $bcc) : null,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $body,
            'status' => 'pending',
        ]);
    }
}
