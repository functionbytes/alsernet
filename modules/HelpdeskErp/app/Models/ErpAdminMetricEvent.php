<?php

namespace Modules\HelpdeskErp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Evento ligero de uso del panel de Gestión en el chat
 * (helpdesk_erp_metrics_events). Lo escribe ErpAdminMetricsRecorder después
 * de enviar la respuesta; nunca bloquea la petición del agente.
 *
 * kind: overview | section | order | delivery_note | invoice | manager_call
 * state: estado normalizado (ok, loading, blocked, unavailable, down,
 *        unlinked, forbidden) o, para manager_call, ok | error | down.
 *
 * @property int $id
 * @property Carbon $occurred_at
 * @property int|null $user_id
 * @property int|null $customer_id
 * @property string $kind
 * @property string|null $section
 * @property string|null $state
 * @property int|null $duration_ms
 * @property int $blocked_count
 */
class ErpAdminMetricEvent extends Model
{
    public const KINDS = ['overview', 'section', 'order', 'delivery_note', 'invoice', 'manager_call'];

    public $timestamps = false;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_erp_metrics_events';

    protected $fillable = ['occurred_at', 'user_id', 'customer_id', 'kind', 'section', 'state', 'duration_ms', 'blocked_count'];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'user_id' => 'integer',
            'customer_id' => 'integer',
            'duration_ms' => 'integer',
            'blocked_count' => 'integer',
        ];
    }
}
