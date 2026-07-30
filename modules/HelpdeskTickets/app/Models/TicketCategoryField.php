<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCategoryField extends Model
{
    public const TYPES = ['text', 'textarea', 'email', 'phone', 'number', 'select', 'radio', 'checkbox', 'date', 'file'];

    public const TYPES_WITH_OPTIONS = ['select', 'radio', 'checkbox'];

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_category_fields';

    protected $fillable = [
        'ticket_category_id',
        'type',
        'key',
        'label',
        'placeholder',
        'help_text',
        'default_value',
        'options',
        'is_required',
        'is_visible',
        'width',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function scopeOrdered($query): mixed
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
