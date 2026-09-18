<?php

namespace Modules\HelpdeskErp\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Helpdesk\Support\Concerns\ScopesCustomerByInbox;
use Modules\HelpdeskErp\Http\Requests\CustomerContextRequest;
use Modules\HelpdeskErp\Http\Requests\ErpHealthRequest;
use Modules\HelpdeskErp\Http\Requests\RefreshCustomerContextRequest;
use Modules\HelpdeskErp\Http\Requests\SearchErpCustomersRequest;
use Modules\HelpdeskErp\Http\Requests\WarmErpCacheRequest;
use Modules\HelpdeskErp\Http\Resources\CustomerContextResource;
use Modules\HelpdeskErp\Jobs\WarmErpCacheJob;
use Modules\HelpdeskErp\Services\CustomerTimelineService;
use Modules\HelpdeskErp\Services\ErpContextService;

class ErpContextController extends Controller
{
    use ScopesCustomerByInbox;

    public function __construct(
        private readonly ErpContextService $service,
        private readonly CustomerTimelineService $timelineService,
    ) {}

    /**
     * GET /api/helpdeskErp/customers/{email}/context
     *
     * Returns the ERP commercial context for the given customer email.
     * Requires permission: helpdeskerp.view
     *
     * @OA\Get(
     *     path="/api/helpdeskErp/customers/{email}/context",
     *     summary="Obtiene contexto comercial ERP del cliente",
     *     tags={"HelpdeskErp"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="email", in="path", required=true, description="Email del cliente", @OA\Schema(type="string", format="email")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Contexto del cliente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", type="object"),
     *                 @OA\Property(property="orders", type="array", @OA\Items()),
     *                 @OA\Property(property="invoices", type="array", @OA\Items()),
     *                 @OA\Property(property="ordersLoading", type="boolean"),
     *                 @OA\Property(property="meta", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso helpdeskerp.view"),
     *     @OA\Response(response=422, description="Email inválido")
     * )
     */
    public function show(CustomerContextRequest $request, string $email): JsonResponse
    {
        $this->assertScopedToCustomerEmail($email, 'helpdeskerp.prospect.view');

        $phone = $request->query('phone') ? (string) $request->query('phone') : null;
        $customerId = $request->query('customer_id') ? (int) $request->query('customer_id') : null;

        $data = $this->service->getCustomerContext($email, $phone, $customerId);

        return response()->json(new CustomerContextResource($data, $email));
    }

    /**
     * POST /api/helpdeskErp/customers/{email}/context/refresh
     *
     * Invalidates the Redis cache for the customer and returns fresh data.
     * Requires permission: helpdeskerp.refresh
     *
     * @OA\Post(
     *     path="/api/helpdeskErp/customers/{email}/context/refresh",
     *     summary="Invalida caché ERP y retorna datos frescos del cliente",
     *     tags={"HelpdeskErp"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="email", in="path", required=true, description="Email del cliente", @OA\Schema(type="string", format="email")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Caché invalidado y datos actualizados",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso helpdeskerp.refresh"),
     *     @OA\Response(response=422, description="Email inválido")
     * )
     */
    public function refresh(RefreshCustomerContextRequest $request, string $email): JsonResponse
    {
        $this->assertScopedToCustomerEmail($email, 'helpdeskerp.prospect.view');

        $this->service->forgetCache($email);
        $data = $this->service->getCustomerContext($email);

        return response()->json([
            'success' => true,
            'message' => 'Caché invalidado. Datos actualizados.',
            'data' => new CustomerContextResource($data, $email),
        ]);
    }

    /**
     * GET /api/helpdeskErp/health
     *
     * Returns the health status of the ERP manager connection.
     * Requires permission: helpdeskerp.health.view
     */
    public function health(ErpHealthRequest $request): JsonResponse
    {
        $result = $this->service->healthCheck();

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * GET /api/helpdeskErp/customers/{id}/orders/{orderId}
     *
     * Proxies to manager /api/erp/customer/{id}/orders/{orderId}.
     * Requires permission: helpdeskerp.orders.detail.view
     */
    public function orderDetail(int $customer, int $order): JsonResponse
    {
        if (! auth()->user()?->can('helpdeskerp.orders.detail.view')) {
            return response()->json(['success' => false, 'message' => 'Sin autorización.'], 403);
        }

        $this->assertScopedToExternalId('erp', $customer, 'helpdeskerp.prospect.view');

        $detail = $this->service->getOrderDetail($customer, $order);

        if ($detail === null) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $detail,
        ]);
    }

    /**
     * GET /api/helpdeskErp/customers/{email}/timeline
     *
     * Returns chronological timeline events for a customer from all sources.
     * Requires permission: helpdeskerp.view
     */
    public function timeline(CustomerContextRequest $request, string $email): JsonResponse
    {
        $this->assertScopedToCustomerEmail($email, 'helpdeskerp.prospect.view');

        $limit = (int) $request->query('limit', 50);
        $timeline = $this->timelineService->getTimeline($email, min(100, max(10, $limit)));

        return response()->json(['data' => $timeline]);
    }

    /**
     * GET /api/helpdeskErp/customers/search
     *
     * Searches customers by email, phone, or NIF. Es una búsqueda libre sobre
     * TODA la base ERP (enumeración de no-clientes), así que exige el permiso
     * dedicado de prospecto, no el permiso base del módulo.
     */
    public function search(SearchErpCustomersRequest $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $type = $request->query('type', 'email');

        if (strlen($q) < 3) {
            return response()->json(['data' => []]);
        }

        try {
            $results = $this->service->searchCustomers($q, $type);
        } catch (\Throwable $e) {
            // El service propaga una excepción cuando el manager responde
            // no-2xx o no es alcanzable (URL mal configurada, caída, etc.).
            // Para el endpoint HTTP el contrato es 200 + data vacía: el panel
            // trata "sin resultados" y "error de plataforma" igual en la UI de
            // búsqueda, y el flag error permite distinguirlo si hace falta.
            return response()->json(['data' => [], 'error' => true]);
        }

        return response()->json(['data' => $results]);
    }

    /**
     * POST /api/helpdeskErp/cache/warm
     *
     * Dispatches bulk cache warming for the given email list (max 50).
     * Requires permission: helpdeskerp.refresh
     */
    public function warmCache(WarmErpCacheRequest $request): JsonResponse
    {
        $emails = array_filter(
            (array) $request->input('emails', []),
            fn ($e): bool => is_string($e) && filter_var($e, FILTER_VALIDATE_EMAIL) !== false
        );
        $emails = array_slice(array_values($emails), 0, 50);

        if (empty($emails)) {
            return response()->json(['ok' => true, 'queued' => 0]);
        }

        WarmErpCacheJob::dispatch($emails);

        return response()->json(['ok' => true, 'queued' => count($emails)]);
    }
}
