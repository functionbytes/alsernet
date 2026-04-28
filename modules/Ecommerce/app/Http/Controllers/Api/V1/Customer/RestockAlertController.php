<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductRestockAlert;

class RestockAlertController extends BaseApiController
{
    public function store(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'email' => ['required_without:user_logged_in', 'email', 'max:191'],
        ]);

        $email = $request->input('email') ?? $request->user()?->email;

        if (! $email) {
            return $this->errorResponse('Se requiere un correo electrónico.', 'EMAIL_REQUIRED', 422);
        }

        ProductRestockAlert::query()->firstOrCreate([
            'product_id' => $product->id,
            'email' => $email,
        ], [
            'customer_id' => $request->user()?->id,
        ]);

        return $this->ok(null, 'Te avisaremos cuando este producto esté disponible.');
    }
}
