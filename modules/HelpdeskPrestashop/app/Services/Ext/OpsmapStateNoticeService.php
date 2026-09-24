<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Avisos al cambiar el estado de un pedido desde el chat, configurables por
 * estado de PrestaShop: "Notificar al cliente" marcado o no por defecto y un
 * aviso libre para el agente. El correo que envía cada estado no se configura
 * aquí: es dato real de PrestaShop (order_state.send_email/template).
 */
class OpsmapStateNoticeService
{
    private const CONNECTION = 'helpdesk';

    private const TABLE = 'helpdesk_ps_state_notices';

    /**
     * Nombre legible de las plantillas de correo de PrestaShop más comunes;
     * las que no estén aquí se muestran con su nombre técnico.
     */
    public const TEMPLATES = [
        'order_conf' => 'Confirmación del pedido',
        'bankwire' => 'Instrucciones de transferencia',
        'cheque' => 'Instrucciones de pago con cheque',
        'payment' => 'Pago aceptado',
        'payment_error' => 'Error en el pago',
        'preparation' => 'Pedido en preparación',
        'shipped' => 'Pedido enviado',
        'in_transit' => 'Pedido en tránsito',
        'order_canceled' => 'Pedido cancelado',
        'refund' => 'Reembolso',
        'outofstock' => 'Producto sin stock',
        'order_changed' => 'Pedido modificado',
    ];

    public function ready(): bool
    {
        try {
            return Schema::connection(self::CONNECTION)->hasTable(self::TABLE);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, array{notify_default: ?bool, agent_notice: ?string}>
     */
    public function all(): array
    {
        if (! $this->ready()) {
            return [];
        }

        $out = [];
        foreach ($this->table()->get() as $row) {
            $out[(int) $row->ps_state_id] = [
                'notify_default' => $row->notify_default === null ? null : (bool) $row->notify_default,
                'agent_notice' => $row->agent_notice !== null && trim((string) $row->agent_notice) !== '' ? (string) $row->agent_notice : null,
            ];
        }

        return $out;
    }

    /**
     * Reemplaza la configuración. Solo se guardan filas que cambian algo
     * respecto al comportamiento por defecto.
     *
     * @param  array<int, array{notify?: string|null, notice?: string|null, name?: string|null}>  $rows
     * @return array{before: array, after: array}
     */
    public function save(array $rows, ?int $userId): array
    {
        $before = $this->all();
        $now = now();
        $insert = [];

        foreach ($rows as $stateId => $row) {
            $stateId = (int) $stateId;
            $notify = match ((string) ($row['notify'] ?? 'default')) {
                'yes' => true,
                'no' => false,
                default => null,
            };
            $notice = trim(strip_tags((string) ($row['notice'] ?? '')));

            if ($stateId <= 0 || ($notify === null && $notice === '')) {
                continue;
            }

            $insert[] = [
                'ps_state_id' => $stateId,
                'ps_state_name' => mb_substr((string) ($row['name'] ?? ''), 0, 255) ?: null,
                'notify_default' => $notify,
                'agent_notice' => $notice !== '' ? mb_substr($notice, 0, 500) : null,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::connection(self::CONNECTION)->transaction(function () use ($insert) {
            $this->table()->delete();
            if ($insert !== []) {
                $this->table()->insert($insert);
            }
        });

        return ['before' => $before, 'after' => $this->all()];
    }

    /**
     * Última modificación (para el pie de la pantalla).
     *
     * @return array{at: ?string, by: ?int}
     */
    public function lastUpdate(): array
    {
        if (! $this->ready()) {
            return ['at' => null, 'by' => null];
        }
        $row = $this->table()->orderByDesc('updated_at')->first(['updated_at', 'updated_by']);

        return ['at' => $row?->updated_at, 'by' => $row?->updated_by !== null ? (int) $row->updated_by : null];
    }

    public static function templateLabel(string $template): string
    {
        return self::TEMPLATES[$template] ?? $template;
    }

    private function table(): Builder
    {
        return DB::connection(self::CONNECTION)->table(self::TABLE);
    }
}
