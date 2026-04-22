<?php

namespace Modules\HelpdeskTickets\Models;

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
        'ticket_comment_id',
        'ticket_item_id',
        'direction',
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
        'raw_email',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'headers' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
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
            'sent' => 'Enviado',
            'delivered' => 'Entregado',
            'bounced' => 'Rebotado',
            'failed' => 'Falló',
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
            'sent' => 'info',
            'delivered' => 'success',
            'bounced' => 'danger',
            'failed' => 'danger',
            default => 'secondary',
        };
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
