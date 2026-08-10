<?php

namespace Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;

/**
 * Un formulario administrable del sitio Alvarez (módulo alsernetforms).
 * `form_key` es la clave que llega en el payload firmado del webhook
 * (FormSubmissionReceiverController la usa para resolver qué categoría de
 * ticket corresponde) -- reemplaza el registro hardcodeado que antes vivía
 * en PHP (FormCategoryRegistry del lado PrestaShop, CATEGORY_SLUGS aquí).
 */
class Form extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_forms';

    protected $fillable = [
        'name',
        'form_key',
        'category_id',
        'description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    /**
     * Tickets generados por este formulario. No hay FK real hacia Form (el
     * ticket solo guarda category_id + custom_fields->form_key en JSON), así
     * que se resuelve vía la categoría -- válido mientras cada formulario
     * tenga su propia categoría exclusiva (así están sembrados los 13).
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id', 'category_id')
            ->where('source', 'formulario');
    }
}
