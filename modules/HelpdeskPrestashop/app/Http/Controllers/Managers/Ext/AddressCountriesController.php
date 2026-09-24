<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HelpdeskPrestashop\Services\Ext\AddressCountriesService;

class AddressCountriesController extends Controller
{
    public function __construct(
        private readonly AddressCountriesService $countries
    ) {}

    /**
     * Lo necesita quien puede crear direcciones aunque no tenga
     * helpdeskprestashop.view (el permiso de alta es independiente).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $allowed = $user?->can('helpdeskprestashop.view')
            || $user?->can('helpdeskprestashop.addresses.manage')
            || $user?->can('helpdeskprestashop.carts.manage');

        if (! $allowed) {
            return response()->json(['success' => false], 403);
        }

        $data = $this->countries->countries();

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no responde: no se pudo cargar la lista de países.'], 503);
        }

        return response()->json(['success' => true] + $data);
    }
}
