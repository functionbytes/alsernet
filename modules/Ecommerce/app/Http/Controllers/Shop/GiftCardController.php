<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\GiftCard;

class GiftCardController extends Controller
{
    public function show(): View
    {
        return view('ecommerce::shop.gift-cards.show');
    }

    public function purchase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'recipient_email' => ['required', 'email', 'max:255'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:500'],
            'buyer_name' => ['required', 'string', 'max:255'],
        ]);

        $customerId = auth('ecommerce')->id();

        GiftCard::query()->create([
            'code' => GiftCard::generateCode(),
            'amount' => $validated['amount'],
            'balance' => $validated['amount'],
            'buyer_customer_id' => $customerId,
            'recipient_email' => $validated['recipient_email'],
            'recipient_name' => $validated['recipient_name'],
            'message' => $validated['message'] ?? null,
            'status' => 'active',
        ]);

        return back()->with('success', 'Gift card creada. Se enviará un correo al destinatario con el código.');
    }

    public function redeem(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $giftCard = GiftCard::query()->where('code', $request->input('code'))->first();

        if (! $giftCard || ! $giftCard->isUsable()) {
            return response()->json([
                'success' => false,
                'message' => 'El código de gift card no es válido o ya fue utilizado.',
            ], 422);
        }

        session(['gift_card_code' => $giftCard->code]);

        return response()->json([
            'success' => true,
            'message' => 'Gift card aplicada correctamente.',
            'balance' => (float) $giftCard->balance,
        ]);
    }
}
