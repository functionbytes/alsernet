<?php

namespace Modules\Erp\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Modules\Erp\Models\Oracle\Cliente\ClienteCent;
use Modules\Erp\Models\Oracle\Otros\Puntofidelizacion;
use Modules\Erp\Models\Oracle\Otros\Vale;
use Modules\Erp\Models\Oracle\Promocion\BonoPromocion;

/**
 * Promociones del cliente: vales, bonos y puntos de fidelización.
 *
 * Base URL: /api/erp/customer/{id}/...
 */
class CustomerPromotionalController extends AbstractCustomerController
{
    /**
     * Vales del cliente.
     *
     * GET /api/erp/customer/{id}/vouchers
     */
    public function vouchers(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:vouchers:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $vouchers = Vale::select([
                'idvale_anterior', 'idvale', 'idcliente', 'idalmacen',
                'importe', 'tipo', 'estado', 'fvalidez', 'fanulacion',
                'observaciones', 'tiene_codigo_comprobacion', 'idvale_original',
            ])
                ->where('idcliente', $id)
                ->orderByDesc('idvale')
                ->get()
                ->map(fn ($v) => [
                    'id' => $v->idvale_anterior,
                    'voucher_id' => $v->idvale,
                    'original_voucher_id' => $v->idvale_original,
                    'warehouse' => $v->idalmacen,
                    'amount' => $v->importe !== null ? (float) $v->importe : null,
                    'type' => $v->tipo,
                    'valid_until' => $v->fvalidez ? Carbon::parse($v->fvalidez)->format('Y-m-d') : null,
                    'cancelled_at' => $v->fanulacion ? Carbon::parse($v->fanulacion)->format('Y-m-d H:i:s') : null,
                    'observations' => $v->observaciones,
                    'has_check_code' => (bool) $v->tiene_codigo_comprobacion,
                    'available' => $v->estado,
                ])->values();

            $now = now();
            $active = $vouchers->filter(fn ($v) => $v['available']
                && ! $v['cancelled_at']
                && (! $v['valid_until'] || Carbon::parse($v['valid_until'])->gte($now)))->count();

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'vouchers' => $vouchers,
                'statistics' => [
                    'vouchers' => [
                        'total' => $vouchers->count(),
                        'active' => $active,
                        'amount_total' => round((float) $vouchers->sum('amount'), 2),
                    ],
                ],
            ];
        }, 'Vouchers');
    }

    /**
     * Bonos del cliente.
     *
     * GET /api/erp/customer/{id}/bonuses
     */
    public function bonuses(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:bonuses:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $bonuses = BonoPromocion::select([
                'idbono_promocion', 'idcliente', 'idalbarancli', 'idlpromocion',
                'idtbono_promocion', 'idcatalogo_consumo', 'idliquidacionbono',
                'fvalidez_desde', 'fvalidez_hasta', 'estado', 'estado_envio',
                'tipo', 'tipotarjeta', 'importe', 'importeminimoventa',
                'enviado_sms', 'enviado_mail', 'enviado_carta', 'bonoimpreso',
                'fecha', 'fconsumo', 'fenvio',
            ])
                ->where('idcliente', $id)
                ->orderByDesc('fecha')
                ->get()
                ->map(fn ($b) => [
                    'id' => $b->idbono_promocion,
                    'delivery_id' => $b->idalbarancli,
                    'promotion_line' => $b->idlpromocion,
                    'bonus_type' => $b->idtbono_promocion,
                    'consumption_catalog' => $b->idcatalogo_consumo,
                    'liquidation' => $b->idliquidacionbono,
                    'amount' => $b->importe !== null ? (float) $b->importe : null,
                    'minimum_purchase' => $b->importeminimoventa !== null ? (float) $b->importeminimoventa : null,
                    'type' => $b->tipo,
                    'card_type' => $b->tipotarjeta,
                    'valid_from' => $b->fvalidez_desde ? Carbon::parse($b->fvalidez_desde)->format('Y-m-d') : null,
                    'valid_until' => $b->fvalidez_hasta ? Carbon::parse($b->fvalidez_hasta)->format('Y-m-d') : null,
                    'date' => $b->fecha ? Carbon::parse($b->fecha)->format('Y-m-d H:i:s') : null,
                    'consumed_at' => $b->fconsumo ? Carbon::parse($b->fconsumo)->format('Y-m-d H:i:s') : null,
                    'sent_at' => $b->fenvio ? Carbon::parse($b->fenvio)->format('Y-m-d H:i:s') : null,
                    'available' => $b->estado,
                    'send_status' => $b->estado_envio,
                    'sent' => [
                        'sms' => (bool) $b->enviado_sms,
                        'email' => (bool) $b->enviado_mail,
                        'mail' => (bool) $b->enviado_carta,
                        'printed' => (bool) $b->bonoimpreso,
                    ],
                ])->values();

            $now = now();
            $active = $bonuses->filter(fn ($b) => $b['available']
                && ! $b['consumed_at']
                && (! $b['valid_until'] || Carbon::parse($b['valid_until'])->gte($now)))->count();

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'bonuses' => $bonuses,
                'statistics' => [
                    'bonuses' => [
                        'total' => $bonuses->count(),
                        'active' => $active,
                        'consumed' => $bonuses->whereNotNull('consumed_at')->count(),
                        'amount_total' => round((float) $bonuses->sum('amount'), 2),
                    ],
                ],
            ];
        }, 'Bonuses');
    }

    /**
     * Puntos de fidelización del cliente.
     *
     * GET /api/erp/customer/{id}/loyalty-points
     */
    public function loyaltyPoints(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:loyalty:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos', 'idtarjeta'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $movements = Puntofidelizacion::select([
                'idpuntofidelizacion', 'idcliente', 'idtarjeta', 'idalmacen',
                'idliquidacion', 'idalbarancli', 'puntos', 'fecha', 'estado',
            ])
                ->where('idcliente', $id)
                ->orderByDesc('fecha')
                ->limit(100)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->idpuntofidelizacion,
                    'card' => $p->idtarjeta,
                    'warehouse' => $p->idalmacen,
                    'liquidation' => $p->idliquidacion,
                    'delivery_id' => $p->idalbarancli,
                    'points' => (int) $p->puntos,
                    'date' => $p->fecha ? Carbon::parse($p->fecha)->format('Y-m-d H:i:s') : null,
                    'available' => $p->estado,
                ])->values();

            $balance = (int) Puntofidelizacion::where('idcliente', $id)
                ->where('estado', 1)
                ->sum('puntos');

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'main_card' => $customer->idtarjeta,
                'balance' => $balance,
                'movements' => $movements,
                'statistics' => [
                    'movements' => ['total' => $movements->count()],
                ],
            ];
        }, 'LoyaltyPoints');
    }
}
