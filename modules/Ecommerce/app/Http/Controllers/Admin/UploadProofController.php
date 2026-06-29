<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Order;

class UploadProofController extends Controller
{
    public function __invoke(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = $request->file('proof')->store('ecommerce/payment-proofs');

        $order->update(['payment_status' => 'pending_review']);

        return response()->json([
            'success' => true,
            'path' => $path,
        ]);
    }
}
