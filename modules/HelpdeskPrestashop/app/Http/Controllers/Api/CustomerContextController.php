<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HelpdeskPrestashop\Http\Requests\CustomerContextRequest;
use Modules\HelpdeskPrestashop\Http\Requests\RefreshCustomerContextRequest;
use Modules\HelpdeskPrestashop\Http\Resources\CustomerContextResource;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

class CustomerContextController extends Controller
{
    public function __construct(
        private readonly PrestashopContextService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/api/helpdeskPrestashop/customers/{email}/context",
     *     summary="Obtiene contexto PrestaShop del cliente (pedidos, historial, carrito)",
     *     tags={"HelpdeskPrestashop"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="email", in="path", required=true, description="Email del cliente", @OA\Schema(type="string", format="email")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Contexto PrestaShop del cliente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="customer", type="object"),
     *             @OA\Property(property="orders", type="array", @OA\Items()),
     *             @OA\Property(property="cart", type="object", nullable=true),
     *             @OA\Property(property="found", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso helpdeskPrestashop.view"),
     *     @OA\Response(response=422, description="Email inválido")
     * )
     */
    public function show(CustomerContextRequest $request, string $email): JsonResponse
    {
        if ($invalid = $this->invalidEmailResponse($email)) {
            return $invalid;
        }

        $context = $this->service->getCustomerContext($email);

        return response()->json(new CustomerContextResource($context));
    }

    public function refresh(RefreshCustomerContextRequest $request, string $email): JsonResponse
    {
        if ($invalid = $this->invalidEmailResponse($email)) {
            return $invalid;
        }

        $this->service->forgetCache($email);
        $context = $this->service->getCustomerContext($email);

        return response()->json(new CustomerContextResource($context));
    }

    private function invalidEmailResponse(string $email): ?JsonResponse
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return response()->json(['message' => 'Email inválido.'], 422);
    }
}
