<?php

namespace Modules\Ecommerce\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Tax;

class TaxApiController extends Controller
{
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'subtotal' => ['required', 'numeric', 'min:0'],
            'country' => ['nullable', 'string', 'max:2'],
        ]);

        $subtotal = (float) $request->input('subtotal');

        $tax = Tax::query()
            ->where('status', 'active')
            ->orderBy('priority')
            ->first();

        if (! $tax) {
            return response()->json([
                'taxAmount' => 0,
                'taxRate' => 0,
                'totalWithTax' => round($subtotal, 2),
            ]);
        }

        $taxRate = $tax->percentage;
        $taxAmount = round($subtotal * ($taxRate / 100), 2);

        return response()->json([
            'taxAmount' => $taxAmount,
            'taxRate' => $taxRate,
            'totalWithTax' => round($subtotal + $taxAmount, 2),
        ]);
    }
}
