<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nota interna de un contacto (Contactos 360 · "Notas internas"). Solo
 * visible para agentes; cada nota guarda quién la escribió y cuándo.
 */
class CustomerNote extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_customer_notes';

    protected $fillable = [
        'customer_id',
        'user_id',
        'body',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
