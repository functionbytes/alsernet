<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnDiscussion extends Model
{
    protected $table = 'return_discussions';
    protected $primaryKey = 'id_return_discussion';

    protected $fillable = [
        'id_return_request', 'id_employee', 'message', 'file_name', 'private'
    ];

    protected $casts = [
        'private' => 'boolean',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnRequest', 'id_return_request', 'id_return_request');
    }

    public function scopePublic($query)
    {
        return $query->where('private', false);
    }

    public function scopePrivate($query)
    {
        return $query->where('private', true);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('id_employee', $employeeId);
    }

    public function scopeByReturn($query, $returnId)
    {
        return $query->where('id_return_request', $returnId);
    }

    public function scopeWithFiles($query)
    {
        return $query->whereNotNull('file_name');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Verificar si el mensaje tiene archivo adjunto
     */
    public function hasAttachment(): bool
    {
        return !empty($this->file_name);
    }

    /**
     * Obtener la ruta completa del archivo adjunto
     */
    public function getAttachmentPath(): ?string
    {
        return $this->file_name ? storage_path('app/returns/discussions/' . $this->file_name) : null;
    }

    /**
     * Verificar si es un mensaje del cliente (id_employee = 0)
     */
    public function isCustomerMessage(): bool
    {
        return $this->id_employee == 0;
    }

    /**
     * Verificar si es un mensaje del administrador
     */
    public function isAdminMessage(): bool
    {
        return $this->id_employee > 0;
    }

    /**
     * Obtener el tipo de autor del mensaje
     */
    public function getAuthorType(): string
    {
        return $this->isCustomerMessage() ? 'customer' : 'admin';
    }

    /**
     * Formatear el mensaje para mostrar
     */
    public function getFormattedMessage(): array
    {
        return [
            'id' => $this->id_return_discussion,
            'message' => $this->message,
            'author_type' => $this->getAuthorType(),
            'is_private' => $this->private,
            'has_attachment' => $this->hasAttachment(),
            'file_name' => $this->file_name,
            'created_at' => $this->created_at,
            'formatted_date' => $this->created_at->format('d/m/Y H:i')
        ];
    }
}
