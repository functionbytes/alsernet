<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\CreateCompensationVoucherRequest;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Vale de compensación desde el chat (pieza 04 del documento "PrestaShop en
 * el chat"): importe fijo, un solo uso, a nombre del cliente de la
 * conversación. Límite por vale según el permiso del agente; cada alta queda
 * en el log de actividad ligada al agente y a la conversación.
 */
class PsVoucherActionsController extends Controller
{
    public function __construct(
        private readonly PrestashopContextService $service
    ) {}

    public function store(CreateCompensationVoucherRequest $request, Customer $customer): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('view', $customer)) {
            return response()->json(['success' => false], 403);
        }

        $limit = $this->limitFor($user);
        if ($limit === null) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para crear vales.'], 403);
        }

        $data = $request->validated();
        $amount = round((float) $data['amount'], 2);

        if ($amount > $limit) {
            return response()->json([
                'success' => false,
                'needs_approval' => true,
                'limit' => $limit,
                'message' => 'El importe supera tu límite por vale ('.number_format($limit, 2, ',', '.').' €). Pide aprobación.',
            ], 422);
        }

        $externalId = $customer->externalIdFor('prestashop');
        $externalId = $externalId !== null ? (int) $externalId : null;

        if (trim((string) $customer->email) === '' && $externalId === null) {
            return response()->json(['success' => false, 'message' => 'El cliente no está vinculado a PrestaShop.'], 422);
        }

        $reasonLabel = config('helpdeskprestashop.vouchers.reasons.'.$data['reason'], $data['reason']);

        // Idempotencia: mismo agente + cliente + importe + motivo en el mismo
        // minuto = doble clic, no un segundo vale.
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', '')) ?: sha1(implode(':', [
            $user->getAuthIdentifier(), $customer->id, $amount, $data['validity_days'], $data['reason'], now()->format('YmdHi'),
        ]));

        try {
            $result = $this->service->createCompensationVoucher(
                (int) round($amount * 100),
                (int) $data['validity_days'],
                $reasonLabel,
                (string) ($user->name ?? $user->email ?? ''),
                $customer->email ?: null,
                $externalId,
                $idempotencyKey,
            );
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha podido crear el vale ahora mismo.'], 503);
        }

        if (! is_array($result) || ! ($result['created'] ?? false)) {
            return response()->json(['success' => false, 'message' => 'PrestaShop ha rechazado el vale.'], 422);
        }

        if (function_exists('activity')) {
            activity('helpdeskprestashop')
                ->causedBy($user)
                ->performedOn($customer)
                ->withProperties([
                    'code' => $result['code'] ?? null,
                    'amount' => $amount,
                    'validity_days' => (int) $data['validity_days'],
                    'reason' => $reasonLabel,
                    'conversation_id' => $data['conversation_id'] ?? null,
                ])
                ->log('ps.voucher.created');
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Límite por vale del agente, o null si no puede crear vales.
     */
    private function limitFor($user): ?float
    {
        if ($user->can('helpdeskprestashop.vouchers.approve')) {
            return (float) config('helpdeskprestashop.vouchers.approver_limit', 150);
        }

        if ($user->can('helpdeskprestashop.vouchers.create')) {
            return (float) config('helpdeskprestashop.vouchers.agent_limit', 25);
        }

        return null;
    }
}
