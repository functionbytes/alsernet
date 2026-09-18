<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskTickets\Database\Factories\TicketTemplateFactory;
use Modules\HelpdeskTickets\Models\Concerns\BelongsToHelpdeskUser;

class TicketTemplate extends Model
{
    use BelongsToHelpdeskUser, HasFactory;

    protected static function newFactory(): TicketTemplateFactory
    {
        return TicketTemplateFactory::new();
    }

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_templates';

    protected $fillable = [
        'name',
        'description',
        'subject',
        'body',
        'category_id',
        'priority',
        'fields',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    /**
     * Dueño de una plantilla personal. Cross-connection (los usuarios viven
     * en la conexion por defecto, no en `helpdesk`) — null en plantillas
     * generales.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsToHelpdeskUser('created_by', 'creator');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Plantillas generales: visibles y aplicables por cualquier usuario.
     */
    public function scopeGeneral(Builder $query): Builder
    {
        return $query->whereNull('created_by');
    }

    /**
     * Plantillas personales de un usuario concreto.
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Todo lo que un usuario puede usar/ver: las generales + las suyas
     * propias. Usado por el selector "Usar plantilla" al crear un ticket.
     */
    public function scopeVisibleTo(Builder $query, int $userId): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('created_by')->orWhere('created_by', $userId));
    }

    public function isGeneral(): bool
    {
        return $this->created_by === null;
    }
}
