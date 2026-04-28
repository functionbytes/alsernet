<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Events\AccountDeleting;

class ProfileController extends BaseApiController
{
    public function destroy(Request $request): JsonResponse
    {
        $customer = $request->user();

        AccountDeleting::dispatch($customer);

        $customer->tokens()->delete();

        return $this->ok(null, 'Solicitud de eliminación recibida. Procesaremos tu cuenta en los próximos días.');
    }
}
