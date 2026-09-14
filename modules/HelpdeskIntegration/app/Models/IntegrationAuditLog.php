<?php

namespace Modules\HelpdeskIntegration\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Models\Concerns\HasCrossDatabaseUserRelation;
use Modules\Helpdesk\Models\Customer;

/**
 * Traza de auditoria de acciones sobre integraciones de un cliente
 * (vincular/desvincular/sincronizar): quien la hizo y cuando.
 */
class IntegrationAuditLog extends Model
{
    use HasCrossDatabaseUserRelation;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_integration_audit_log';

    protected $fillable = [
        'customer_id',
        'platform',
        'action',
        'external_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsToUser();
    }

    /**
     * Filas anteriores a X dias (para el comando de purga).
     */
    public function scopeOlderThan(Builder $query, int $days): void
    {
        $query->where('created_at', '<', now()->subDays($days));
    }
}
