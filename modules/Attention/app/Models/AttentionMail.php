<?php

namespace Modules\Attention\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Attention\Traits\HasUid;
use Modules\Mailer\Models\MailerTemplate;

/**
 * Log de emails enviados relacionados con un PQRSF
 */
class AttentionMail extends Model
{
    use HasUid;

    protected $table = 'attention_mails';

    protected $fillable = [
        'uid',
        'attention_id',
        'email_type',
        'recipient_email',
        'subject',
        'body_html',
        'body_text',
        'template_id',
        'sent_by',
        'metadata',
        'status',
        'error_message',
        'sent_at',
        'failed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Email type labels for display
     */
    public const EMAIL_TYPE_LABELS = [
        'confirmation' => 'Confirmación de recepción',
        'assigned' => 'Notificación de asignación',
        'in_process' => 'Notificación en trámite',
        'resolution' => 'Notificación de resolución',
        'custom' => 'Correo personalizado',
    ];

    /**
     * Attention this mail belongs to
     */
    public function attention(): BelongsTo
    {
        return $this->belongsTo(Attention::class, 'attention_id');
    }

    /**
     * Relation to the mail template used
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MailerTemplate::class, 'template_id');
    }

    /**
     * Relation to the user who sent the email
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Get the email type label for display
     */
    public function getEmailTypeLabelAttribute(): string
    {
        return self::EMAIL_TYPE_LABELS[$this->email_type] ?? ucfirst($this->email_type);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'sent' => 'success',
            'failed' => 'danger',
            'queued' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'sent' => 'Enviado',
            'failed' => 'Fallido',
            'queued' => 'En cola',
            default => ucfirst($this->status),
        };
    }

    /**
     * Scope to filter by attention
     */
    public function scopeForAttention($query, int $attentionId)
    {
        return $query->where('attention_id', $attentionId);
    }

    /**
     * Scope to filter by email type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('email_type', $type);
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope sent emails
     */
    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    /**
     * Scope failed emails
     */
    public function scopeFailed($query)
    {
        return $query->whereNotNull('failed_at');
    }

    /**
     * Scope by email type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('email_type', $type);
    }

    /**
     * Check if email was sent successfully
     */
    public function wasSent(): bool
    {
        return ! is_null($this->sent_at);
    }

    /**
     * Check if email failed
     */
    public function hasFailed(): bool
    {
        return ! is_null($this->failed_at);
    }

    /**
     * Create a new attention mail record
     */
    public static function logEmail(
        Attention $attention,
        string $emailType,
        string $subject,
        string $bodyHtml,
        ?string $bodyText = null,
        ?int $templateId = null,
        ?int $sentBy = null,
        array $metadata = []
    ): self {
        return self::create([
            'attention_id' => $attention->id,
            'email_type' => $emailType,
            'recipient_email' => $attention->customer_email ?? '',
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText ?? strip_tags($bodyHtml),
            'template_id' => $templateId,
            'sent_by' => $sentBy ?? auth()->id(),
            'metadata' => $metadata,
            'status' => 'queued',
        ]);
    }

    /**
     * Mark email as sent
     */
    public function markAsSent(): self
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark email as failed
     */
    public function markAsFailed(string $errorMessage): self
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);

        return $this;
    }
}
