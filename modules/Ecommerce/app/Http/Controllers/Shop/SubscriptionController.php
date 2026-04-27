<?php

namespace Modules\Ecommerce\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Subscription;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:ecommerce');
    }

    public function index(): View
    {
        $subscriptions = Subscription::query()
            ->where('customer_id', auth('ecommerce')->id())
            ->with('product')
            ->latest()
            ->get();

        return view('ecommerce::shop.account.subscriptions', compact('subscriptions'));
    }

    public function subscribe(Request $request, Product $product): RedirectResponse
    {
        if (! $product->is_subscription) {
            return back()->with('error', 'Este producto no acepta suscripciones.');
        }

        $request->validate(['qty' => ['nullable', 'integer', 'min:1']]);

        $price = $product->price;
        if ($product->subscription_discount_percent > 0) {
            $price = $price * (1 - ($product->subscription_discount_percent / 100));
        }

        Subscription::query()->create([
            'customer_id' => auth('ecommerce')->id(),
            'product_id' => $product->id,
            'qty' => (int) $request->input('qty', 1),
            'interval' => $product->subscription_interval ?? 'monthly',
            'price' => round($price, 2),
            'status' => 'active',
            'next_billing_at' => match ($product->subscription_interval ?? 'monthly') {
                'weekly' => now()->addWeek(),
                'quarterly' => now()->addMonths(3),
                'yearly' => now()->addYear(),
                default => now()->addMonth(),
            },
        ]);

        return redirect()->route('account.subscriptions.index')->with('success', 'Suscripción activada.');
    }

    public function pause(Subscription $subscription): RedirectResponse
    {
        $this->authorizeOwnership($subscription);
        $subscription->pause();

        return back()->with('success', 'Suscripción pausada.');
    }

    public function resume(Subscription $subscription): RedirectResponse
    {
        $this->authorizeOwnership($subscription);
        $subscription->resume();

        return back()->with('success', 'Suscripción reactivada.');
    }

    public function cancel(Subscription $subscription): RedirectResponse
    {
        $this->authorizeOwnership($subscription);
        $subscription->cancel();

        return back()->with('success', 'Suscripción cancelada.');
    }

    private function authorizeOwnership(Subscription $subscription): void
    {
        if ($subscription->customer_id !== auth('ecommerce')->id()) {
            abort(403);
        }
    }
}
