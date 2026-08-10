<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TicketMail extends Model
{
    use SoftDeletes;

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
        return $this->belongsTo(User::class, 'user_id');
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

    /**
     * Get the original email this is replying to
     */
    public function getOriginalEmail(): ?self
    {
        if (! $this->in_reply_to) {
            return null;
        }

        return static::where('message_id', $this->in_reply_to)->first();
    }

    /**
     * Get all emails in the thread (conversation chain)
     */
    public function getThread(): Collection
    {
        // Start with all emails for this ticket
        $allEmails = static::where('ticket_id', $this->ticket_id)
            ->oldest()
            ->get();

        // Build thread by tracing references
        return $this->buildThreadChain($allEmails);
    }

    /**
     * Get all replies to this email
     */
    public function getReplies(): Collection
    {
        return static::where('ticket_id', $this->ticket_id)
            ->where(function ($query) {
                $query->where('in_reply_to', $this->message_id)
                    ->orWhere('references', 'like', '%'.$this->message_id.'%');
            })
            ->oldest()
            ->get();
    }

    /**
     * Get the root email in the thread
     */
    public function getRootEmail(): self
    {
        $current = $this;

        while ($parent = $current->getOriginalEmail()) {
            $current = $parent;
        }

        return $current;
    }

    /**
     * Build thread chain from all emails
     */
    private function buildThreadChain(Collection $allEmails): Collection
    {
        $chain = collect();
        $current = $this;

        // Go to root first
        while ($parent = $current->getOriginalEmail()) {
            $current = $parent;
        }

        // Build chain forward
        $visited = [];

        while ($current && ! in_array($current->id, $visited)) {
            $visited[] = $current->id;
            $chain->push($current);

            // Find next reply
            $nextEmail = static::where('ticket_id', $this->ticket_id)
                ->where(function ($query) use ($current) {
                    $query->where('in_reply_to', $current->message_id)
                        ->orWhere('references', 'like', '%'.$current->message_id.'%');
                })
                ->whereNotIn('id', $visited)
                ->oldest()
                ->first();

            $current = $nextEmail;
        }

        return $chain;
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
     * Parse incoming email from raw content
     */
    public static function parseIncomingEmail(string $rawEmail): array
    {
        // Basic parsing - in production, use a library like php-mime-mail-parser
        $lines = explode("\n", $rawEmail);
        $headers = [];
        $body = '';
        $bodyStarted = false;

        foreach ($lines as $line) {
            if (! $bodyStarted) {
                if (trim($line) === '') {
                    $bodyStarted = true;

                    continue;
                }

                if (strpos($line, ':') !== false) {
                    [$key, $value] = explode(':', $line, 2);
                    $headers[trim($key)] = trim($value);
                }
            } else {
                $body .= $line."\n";
            }
        }

        return [
            'message_id' => $headers['Message-ID'] ?? null,
            'in_reply_to' => $headers['In-Reply-To'] ?? null,
            'references' => $headers['References'] ?? null,
            'from' => $headers['From'] ?? null,
            'to' => $headers['To'] ?? null,
            'cc' => $headers['Cc'] ?? null,
            'bcc' => $headers['Bcc'] ?? null,
            'subject' => $headers['Subject'] ?? null,
            'body_text' => trim($body),
            'body_html' => null,
            'headers' => $headers,
            'raw_email' => $rawEmail,
        ];
    }

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
            'message_id' => '<'.Str::uuid().'@'.config('app.name').'>',
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
