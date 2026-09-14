<?php

namespace Modules\HelpdeskIntegration\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Models\Concerns\HasCrossDatabaseUserRelation;
use Modules\Helpdesk\Models\Customer;

/**
 * Codigo OTP para verificar la identidad de un cliente antes de exponer sus
 * integraciones externas. `expires_at` acota la ventana para introducir el
 * codigo; `verified_at` marca el inicio de la sesion verificada (el TTL de
 * sesion se aplica en CustomerIdentityVerificationService::isVerified()).
 */
class CustomerIdentityVerification extends Model
{
    use HasCrossDatabaseUserRelation;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_customer_identity_verifications';

    protected $fillable = [
        'customer_id',
        'conversation_id',
        'channel',
        'code_hash',
        'attempts',
        'expires_at',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsToUser('verified_by', 'verifiedBy');
    }

    /**
     * Filas anteriores a X dias (para el comando de purga). Se basa en
     * created_at: incluso verificada, la fila deja de ser relevante mucho
     * antes de la retencion por defecto (30 dias vs sesion de 30 min).
     */
    public function scopeOlderThan(Builder $query, int $days): void
    {
        $query->where('created_at', '<', now()->subDays($days));
    }
}
