<?php

namespace Modules\Forms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmissionAction extends Model
{
    protected $table = 'form_submission_actions';

    protected $fillable = ['submission_id', 'user_id', 'action', 'description'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'status_changed' => 'Cambio de estado',
            'assigned' => 'Asignación actualizada',
            'email_sent' => 'Email enviado',
            'file_uploaded' => 'Archivo adjunto',
            'file_deleted' => 'Archivo eliminado',
            'note_added' => 'Nota añadida',
            'created' => 'Solicitud recibida',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }
}
