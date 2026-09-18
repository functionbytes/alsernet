<?php

namespace Modules\HelpdeskErp\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Support\Concerns\ScopesCustomerByInbox;
use Modules\HelpdeskErp\Http\Resources\CustomerContextResource;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
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

        // customer_id se resuelve server-side a partir del email ya validado
        // arriba — ver mismo fix en el gemelo API (ErpContextController::show).
        $customerId = Customer::where('email', $email)->value('id');
        $data = $this->service->getCustomerContext($email, null, $customerId);

        return response()->json([
            'success' => true,
            'data' => (new CustomerContextResource($data, $email))->toArray($request),
        ]);
    }

    /**
     * Reintento manual de la búsqueda del cliente en el ERP.
     *
     * Lo pide un agente desde el aviso "Sin cliente en gestión", así que salta
     * el enfriamiento: si alguien está mirando la ficha es porque sabe algo que
     * el automatismo no —acaban de dar de alta al cliente en gestión, o el ERP
     * ya volvió a estar en pie.
     */
    public function relink(Request $request, int $customerId): JsonResponse
    {
        if (! $request->user()?->can('helpdeskerp.view')) {
            return response()->json(['success' => false], 403);
        }

        $customer = Customer::find($customerId);

        if ($customer === null) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado.'], 404);
        }

        // El agente solo puede reintentar sobre clientes de sus bandejas, igual
        // que para ver su contexto ERP.
        $this->assertScopedToCustomerEmail((string) $customer->email, 'helpdeskerp.prospect.view');

        if (! helpdesk_erp_enabled()) {
            return response()->json(['success' => false, 'message' => 'La integración con gestión está desactivada.'], 422);
        }

        LinkCustomerToErpJob::dispatch($customer->id, null, null, force: true);

        return response()->json([
            'success' => true,
            'message' => 'Buscando el cliente en gestión…',
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
