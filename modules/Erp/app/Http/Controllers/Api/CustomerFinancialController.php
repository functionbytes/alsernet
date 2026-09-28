<?php

namespace Modules\Erp\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Models\Oracle\Cliente\ClienteCent;
use Modules\Erp\Models\Oracle\Cobro\CobrocliCentral;
use Modules\Erp\Models\Oracle\Cobro\DeudacliCentral;
use Modules\Erp\Models\Oracle\Factura\FacturacliCentral;
use Modules\Erp\Models\Oracle\Otros\Puntofidelizacion;
use Modules\Erp\Support\ErpErrorSanitizer;

/**
 * Situación financiera del cliente: cobros, deudas y saldo.
 *
 * Base URL: /api/erp/customer/{id}/...
 */
class CustomerFinancialController extends AbstractCustomerController
{
    /**
     * Cobros del cliente (paginado).
     *
     * GET /api/erp/customer/{id}/payments?limit=10&offset=0&from=&to=
     */
    public function payments(int $id, Request $request): JsonResponse
    {
        try {
            $limit = max(1, min((int) $request->get('limit', 10), 100));
            $offset = max(0, (int) $request->get('offset', 0));

            $query = CobrocliCentral::select([
                'idcobrocli_central', 'idcobrocli', 'idcliente', 'idformapago',
                'idtransportista', 'idvale', 'idcaja', 'estado',
                'importe_cobrado', 'importe_libre', 'fcobro', 'fcreacion', 'fmodificacion',
            ])
                ->where('idcliente', $id)
                ->whereNull('fbaja');

            if ($request->filled('from')) {
                $query->where('fcobro', '>=', $request->get('from'));
            }

            if ($request->filled('to')) {
                $query->where('fcobro', '<=', $request->get('to'));
            }

            $payments = $query->orderByDesc('fcobro')
                ->offset($offset)
                ->limit($limit + 1)
                ->get();

            $hasMore = $payments->count() > $limit;
            if ($hasMore) {
                $payments = $payments->slice(0, $limit);
            }

            $data = $payments->map(fn ($p) => [
                'id' => $p->idcobrocli_central,
                'payment_id' => $p->idcobrocli,
                'method' => $p->idformapago,
                'voucher' => $p->idvale,
                'cash_register' => $p->idcaja,
                'transporter' => $p->idtransportista,
                'amount_collected' => (float) $p->importe_cobrado,
                'amount_free' => $p->importe_libre !== null ? (float) $p->importe_libre : null,
                'date' => $p->fcobro ? Carbon::parse($p->fcobro)->format('Y-m-d H:i:s') : null,
                'status' => $p->estado,
                'created' => $p->fcreacion?->format('Y-m-d H:i:s'),
                'updated' => $p->fmodificacion?->format('Y-m-d H:i:s'),
            ])->values();

            return response()->json([
                'success' => true,
                'data' => $this->cleanUtf8Array($data->all()),
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'count' => $payments->count(),
                    'hasMore' => $hasMore,
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('Error CustomerController@payments', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json(['success' => false, 'error' => ErpErrorSanitizer::forClient($e)], 500);
        }
    }

    /**
     * Deudas vivas del cliente (JOIN con albaranes).
     *
     * GET /api/erp/customer/{id}/debts
     */
    public function debts(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:debts:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos', 'riesgo', 'riesgomaximo'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $debts = DeudacliCentral::query()
                ->from('deudacli_central as d')
                ->join('albarancli_central as a', 'd.idalbarancli_central', '=', 'a.idalbarancli_central')
                ->where('a.idcliente', $id)
                ->whereNull('d.fbaja')
                ->select([
                    'd.iddeudacli_central as id',
                    'd.iddeudacli as debt_id',
                    'd.idcobrocli_central as payment_id',
                    'd.idalbarancli_central as delivery_id',
                    'a.nalbarancli as delivery_number',
                    'a.falbaran as delivery_date',
                    'd.idformapago as payment_method',
                    'd.importe as amount',
                    'd.estado as status',
                    'd.fcreacion as created',
                ])
                ->orderByDesc('d.fcreacion')
                ->get()
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'debt_id' => $d->debt_id,
                    'payment_id' => $d->payment_id,
                    'delivery_id' => $d->delivery_id,
                    'delivery_number' => $d->delivery_number,
                    'delivery_date' => $d->delivery_date ? Carbon::parse($d->delivery_date)->format('Y-m-d') : null,
                    'payment_method' => $d->payment_method,
                    'amount' => (float) $d->amount,
                    'status' => (bool) $d->status,
                    'created' => $d->created ? Carbon::parse($d->created)->format('Y-m-d H:i:s') : null,
                ])->values();

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'risk' => [
                    'current' => $customer->riesgo !== null ? (float) $customer->riesgo : null,
                    'max_allowed' => $customer->riesgomaximo !== null ? (float) $customer->riesgomaximo : null,
                ],
                'debts' => $debts,
                'statistics' => [
                    'debts' => [
                        'total' => $debts->count(),
                        'amount_total' => round((float) $debts->sum('amount'), 2),
                    ],
                ],
            ];
        }, 'Debts');
    }

    /**
     * Saldo agregado del cliente (facturado, cobrado, deuda).
     *
     * GET /api/erp/customer/{id}/balance
     */
    public function balance(int $id): JsonResponse
    {
        return $this->cachedSimple("customer:balance:{$id}", $id, function ($id) {
            $customer = ClienteCent::select(['idcliente', 'nombre', 'apellidos', 'riesgo', 'riesgomaximo'])
                ->whereNull('fbaja')
                ->findOrFail($id);

            $invoiced = (float) FacturacliCentral::where('idcliente', $id)
                ->whereNull('fbaja')
                ->join('lfacturacli_central as l', 'facturacli_central.idfacturacli', '=', 'l.idfacturacli')
                ->sum('l.total_con_impuestos');

            $collected = (float) CobrocliCentral::where('idcliente', $id)
                ->whereNull('fbaja')
                ->sum('importe_cobrado');

            $debt = (float) DeudacliCentral::query()
                ->from('deudacli_central as d')
                ->join('albarancli_central as a', 'd.idalbarancli_central', '=', 'a.idalbarancli_central')
                ->where('a.idcliente', $id)
                ->whereNull('d.fbaja')
                ->sum('d.importe');

            $loyaltyPoints = (int) Puntofidelizacion::where('idcliente', $id)
                ->where('estado', 1)
                ->sum('puntos');

            return [
                'id' => $customer->idcliente,
                'label' => $customer->nombre,
                'surnames' => $customer->apellidos,
                'balance' => [
                    'invoiced' => round($invoiced, 2),
                    'collected' => round($collected, 2),
                    'pending' => round($debt, 2),
                ],
                'risk' => [
                    'current' => $customer->riesgo !== null ? (float) $customer->riesgo : null,
                    'max_allowed' => $customer->riesgomaximo !== null ? (float) $customer->riesgomaximo : null,
                ],
                'loyalty_points' => $loyaltyPoints,
            ];
        }, 'Balance');
    }
}
