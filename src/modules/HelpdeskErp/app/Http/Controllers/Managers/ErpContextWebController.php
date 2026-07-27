<?php

namespace Modules\HelpdeskErp\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Support\Concerns\ScopesCustomerByInbox;
use Modules\HelpdeskErp\Http\Resources\CustomerContextResource;
use Modules\HelpdeskErp\Services\ErpContextService;

class ErpContextWebController extends Controller
{
    use ScopesCustomerByInbox;

    public function __construct(
        private readonly ErpContextService $service,
    ) {}

    public function context(Request $request): JsonResponse
    {
        if (! $request->user()?->can('helpdeskerp.view')) {
            return response()->json(['success' => false], 403);
        }

        $email = trim((string) $request->query('email', ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'Email inválido.'], 422);
        }

        // Consultar un email que NO es cliente local (prospecto) expone datos ERP
        // reales (balance/crédito/pedidos), así que exige el permiso dedicado —
        // igual que el gemelo API. Sin este arg, ScopesCustomerByInbox permite
        // por defecto el acceso a prospectos (bypass del gate para helpdesk-agent).
        $this->assertScopedToCustomerEmail($email, 'helpdeskerp.prospect.view');

        $customerId = $request->query('customer_id') ? (int) $request->query('customer_id') : null;
        $data = $this->service->getCustomerContext($email, null, $customerId);

        return response()->json([
            'success' => true,
            'data' => (new CustomerContextResource($data, $email))->toArray($request),
        ]);
    }

    public function orderDetail(Request $request, int $customerId, int $orderId): JsonResponse
    {
        if (! $request->user()?->can('helpdeskerp.view')) {
            return response()->json(['success' => false], 403);
        }

        // Mismo gate de prospecto que context()/API: un customerId ERP sin cliente
        // local vinculado no debe exponer el detalle del pedido a un agente sin
        // helpdeskerp.prospect.view.
        $this->assertScopedToExternalId('erp', $customerId, 'helpdeskerp.prospect.view');

        $detail = $this->service->getOrderDetail($customerId, $orderId);

        if ($detail === null) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'data' => $detail]);
    }
}
