<?php

namespace Modules\Forms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Forms\Enums\FormEmailStatus;

class FormSubmissionEmail extends Model
{
    protected $table = 'form_submission_emails';

    protected $fillable = [
        'submission_id',
        'email_type',
        'recipient_email',
        'subject',
        'body_html',
        'status',
        'sent_by',
        'sent_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => FormEmailStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function scopeSent($query)
    {
        return $query->where('status', FormEmailStatus::Sent);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', FormEmailStatus::Failed);
    }

    public function getEmailTypeLabelAttribute(): string
    {
        return match ($this->email_type) {
            'confirmation' => 'Confirmación al solicitante',
            'admin' => 'Notificación al administrador',
            'custom' => 'Correo personalizado',
            'resend' => 'Reenvío',
            default => ucfirst($this->email_type),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            FormEmailStatus::Sent => 'Enviado',
            FormEmailStatus::Failed => 'Fallido',
            FormEmailStatus::Queued => 'En cola',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            FormEmailStatus::Sent => 'success',
            FormEmailStatus::Failed => 'danger',
            FormEmailStatus::Queued => 'warning',
        };
    }

    public static function log(
        FormSubmission $submission,
        string $type,
        string $recipient,
        string $subject,
        ?string $bodyHtml = null,
        ?int $sentBy = null
    ): self {
        return self::create([
            'submission_id' => $submission->id,
            'email_type' => $type,
            'recipient_email' => $recipient,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'status' => FormEmailStatus::Queued,
            'sent_by' => $sentBy,
        ]);
    }

    public function markAsSent(): self
    {
        $this->update(['status' => FormEmailStatus::Sent, 'sent_at' => now()]);

        return $this;
    }

    public function markAsFailed(string $error): self
    {
        $this->update(['status' => FormEmailStatus::Failed, 'error_message' => $error]);

        return $this;
    }
}
