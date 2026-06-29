<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\GiftCard;

class GiftCardController extends Controller
{
    public function index(Request $request): View
    {
        $giftCards = GiftCard::query()
            ->with('buyer')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('search'), fn ($q, $s) => $q->where('code', 'like', "%{$s}%")
                ->orWhere('recipient_email', 'like', "%{$s}%"))
            ->latest()
            ->paginate(20);

        return view('ecommerce::gift-cards.index', compact('giftCards'));
    }

    public function create(): View
    {
        return view('ecommerce::gift-cards.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        GiftCard::query()->create([
            ...$validated,
            'code' => GiftCard::generateCode(),
            'balance' => $validated['amount'],
            'status' => 'active',
        ]);

        return redirect()->route('ecommerce.gift-cards.index')->with('success', 'Gift card creada correctamente.');
    }

    public function show(GiftCard $giftCard): View
    {
        $giftCard->load('buyer', 'order');

        return view('ecommerce::gift-cards.show', compact('giftCard'));
    }

    public function destroy(GiftCard $giftCard): RedirectResponse
    {
        $giftCard->delete();

        return redirect()->route('ecommerce.gift-cards.index')->with('success', 'Gift card eliminada correctamente.');
    }
}
