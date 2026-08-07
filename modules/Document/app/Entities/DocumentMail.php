<?php

namespace Modules\Document\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Document\Traits\HasUid;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\Mailer\Models\MailerTemplate;

class DocumentMail extends Model
{
    use HasUid;

    protected $table = 'document_mails';

    /**
     * delivery_status se añade siempre al JSON (accesor más abajo); consulta emailLog()
     * bajo el capó, así que en listados usar ->with('emailLog') para evitar N+1.
     */
    protected $appends = ['delivery_status'];

    protected $fillable = [
        'uid',
        'document_id',
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
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Email type labels for display
     */
    public const EMAIL_TYPE_LABELS = [
        'request' => 'Solicitud inicial',
        'reminder' => 'Recordatorio',
        'upload' => 'Confirmación de carga',
        'approval' => 'Aprobación',
        'rejection' => 'Rechazo',
        'missing' => 'Documentos faltantes',
        'custom' => 'Correo personalizado',
    ];

    /**
     * Relation to the document
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
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
     * Fila correlacionada en el log central de emails (modules/HelpdeskEmailLog),
     * fuente de verdad del estado de entrega REAL (queued/sent/failed y, cuando exista,
     * bounced). Correlación exacta 1:1 vía external_id = this->uid, fijado por
     * DocumentCustomMail::getEmailLogExternalId(). Null en envíos previos a esta
     * correlación (uid no coincide con ningún external_id) o si HelpdeskEmailLog
     * está desactivado (helpdesk_emaillog_enabled() = false, no se crea ninguna fila).
     */
    public function emailLog(): HasOne
    {
        return $this->hasOne(EmailLog::class, 'external_id', 'uid')->latestOfMany();
    }

    /**
     * Estado real de entrega: el de EmailLog si hay correlación, si no cae al status
     * optimista de esta misma tabla (marcado 'sent' al encolar, no al entregar de verdad).
     */
    public function getDeliveryStatusAttribute(): string
    {
        return $this->emailLog?->status?->value ?? $this->status;
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
     * Scope to filter by document
     */
    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
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
     * Create a new document mail record
     */
    public static function logEmail(
        Document $document,
        string $emailType,
        string $subject,
        string $bodyHtml,
        ?string $bodyText = null,
        ?int $templateId = null,
        ?int $sentBy = null,
        array $metadata = []
    ): self {
        return self::create([
            'document_id' => $document->id,
            'email_type' => $emailType,
            'recipient_email' => $document->customer_email ?? '',
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
        ]);

        return $this;
    }
}
