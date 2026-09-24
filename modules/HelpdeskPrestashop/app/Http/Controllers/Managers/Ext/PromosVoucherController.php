<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\PromosVoucherEditRequest;
use Modules\HelpdeskPrestashop\Services\Ext\PromosVoucherService;

/**
 * Editar cupón (pieza 33 · ps-voucher-edit): importe o porcentaje, mínimo,
 * caducidad y usos de un cupón DEL CLIENTE. Solo se modifica en sitio si
 * nunca entró en un pedido; con usos se ofrece duplicarlo con los datos
 * editados. La propiedad del cupón y los usos los comprueba el puente; aquí
 * el permiso, el acceso al cliente (CustomerPolicy), el límite de importe
 * del agente y el registro de actividad.
 */
class PromosVoucherController extends Controller
{
    public function __construct(
        private readonly PromosVoucherService $promos
    ) {}

    public function show(Request $request, Customer $customer, int $voucher): JsonResponse
    {
        $user = $request->user();

        if (! $user?->can('helpdeskprestashop.vouchers.edit') || ! $user->can('view', $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (($resp = $this->denyUnlinked($customer)) !== null) {
            return $resp;
        }

        try {
            $data = $this->promos->voucherInfo($customer, $voucher);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no responde ahora mismo.'], 503);
        }

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'Ese cupón no es de este cliente.'], 404);
        }

        return response()->json(['success' => true, 'data' => $data, 'limits' => $this->limits($user)]);
    }

    public function update(PromosVoucherEditRequest $request, Customer $customer, int $voucher): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('view', $customer)) {
            return response()->json(['success' => false], 403);
        }

        if (($resp = $this->denyUnlinked($customer)) !== null) {
            return $resp;
        }

        $data = $request->validated();
        $limits = $this->limits($user);

        // Idempotencia: mismo agente + cupón + valores en el mismo minuto =
        // doble clic. Con la ventana de un minuto un segundo "Duplicar"
        // deliberado más tarde sí crea otra copia.
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', '')) ?: sha1(implode(':', [
            $user->getAuthIdentifier(), $customer->id, $voucher, json_encode(array_diff_key($data, ['conversation_id' => 1])), now()->format('YmdHi'),
        ]));

        try {
            $result = $this->promos->editVoucher(
                $customer,
                $voucher,
                (string) $data['mode'],
                $data,
                (float) $limits['max_amount'],
                (string) ($user->name ?? $user->email ?? ''),
                $idempotencyKey,
            );
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha podido guardar el cupón ahora mismo.'], 503);
        }

        if ($result === null || ($result['error'] ?? null) === 'not_found') {
            return response()->json(['success' => false, 'message' => 'Ese cupón no es de este cliente.'], 404);
        }

        if (! ($result['saved'] ?? false)) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'rejected',
                'uses' => $result['uses'] ?? null,
                'message' => $this->errorMessage($result),
            ], 422);
        }

        $saved = (array) ($result['voucher'] ?? []);

        if (function_exists('activity')) {
            activity('helpdeskprestashop')
                ->causedBy($user)
                ->performedOn($customer)
                ->withProperties([
                    'mode' => $result['mode'] ?? $data['mode'],
                    'source_voucher_id' => $voucher,
                    'voucher_id' => $saved['id'] ?? null,
                    'code' => $saved['code'] ?? null,
                    'amount' => $data['amount'] ?? null,
                    'percent' => $data['percent'] ?? null,
                    'minimum' => (float) $data['minimum'],
                    'date_to' => $data['date_to'],
                    'quantity' => (int) $data['quantity'],
                    'conversation_id' => $data['conversation_id'] ?? null,
                ])
                ->log(($result['mode'] ?? $data['mode']) === 'duplicate' ? 'ps.voucher.duplicated' : 'ps.voucher.edited');
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Límites del agente para SUBIR un cupón. El de importe es el mismo que
     * al crear un vale de compensación (config vouchers.*): editar no puede
     * ser una puerta trasera para saltárselo.
     *
     * @return array{max_amount: float, max_percent: float, max_quantity: int, max_validity_days: int}
     */
    private function limits($user): array
    {
        $cfg = (array) config('helpdeskprestashop.ext.promos.edit', []);

        $maxAmount = $user->can('helpdeskprestashop.vouchers.approve')
            ? (float) config('helpdeskprestashop.vouchers.approver_limit', 150)
            : (float) config('helpdeskprestashop.vouchers.agent_limit', 25);

        return [
            'max_amount' => $maxAmount,
            'max_percent' => (float) ($cfg['max_percent'] ?? 30),
            'max_quantity' => (int) ($cfg['max_quantity'] ?? 5),
            'max_validity_days' => (int) ($cfg['max_validity_days'] ?? 365),
        ];
    }

    private function denyUnlinked(Customer $customer): ?JsonResponse
    {
        if (trim((string) $customer->email) === '' && $customer->externalIdFor('prestashop') === null) {
            return response()->json(['success' => false, 'message' => 'El cliente no está vinculado a PrestaShop.'], 422);
        }

        return null;
    }

    private function errorMessage(array $result): string
    {
        $limit = $result['limit'] ?? null;

        return match ($result['error'] ?? null) {
            'voucher_used' => 'El cupón ya se ha usado en '.((int) ($result['uses'] ?? 1)).' pedido(s): no se puede modificar, pero puedes duplicarlo.',
            'automatic_rule' => 'Es una regla automática de la tienda: se regenera sola y no se puede editar.',
            'gift_rule' => 'El cupón incluye un producto de regalo: no se puede duplicar desde el chat.',
            'save_failed' => 'PrestaShop no ha aceptado los datos del cupón.',
            'over_limit' => 'Supera tu límite para subir el cupón'.(is_numeric($limit)
                ? ' ('.number_format((float) $limit, 2, ',', '.').(($result['unit'] ?? null) === 'percent' ? ' %' : ' €').')'
                : '').'.',
            'invalid_amount' => 'El importe no es válido.',
            'invalid_percent' => 'El porcentaje no es válido.',
            'invalid_minimum' => 'El pedido mínimo no es válido.',
            'invalid_quantity' => 'Los usos no son válidos'.(is_numeric($limit) ? ' (máximo '.(int) $limit.')' : '').'.',
            'invalid_date' => 'La fecha de caducidad no es válida'.(is_string($limit) ? ' (como mucho hasta el '.Carbon::parse($limit)->format('d/m/Y').')' : '').'.',
            default => 'PrestaShop ha rechazado el cambio.',
        };
    }
}
