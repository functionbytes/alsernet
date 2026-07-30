<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Modules\Helpdesk\Models\Customer;
use Modules\Remarketing\Models\Cart;
use Modules\Remarketing\Models\Customer as RemarketingCustomer;
use Modules\Remarketing\Models\Order;

class CustomerEcommerceController extends Controller
{
    /**
     * Plataformas de CustomerExternalId consideradas e-commerce: su external_id
     * corresponde al id del cliente en la tienda que alimenta Remarketing.
     */
    private const ECOMMERCE_PLATFORMS = ['prestashop', 'ecommerce', 'shopify', 'woocommerce'];

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        if (! class_exists(RemarketingCustomer::class)) {
            return response()->json([
                'success' => true,
                'orders' => [],
                'carts' => [],
                'stats' => null,
            ]);
        }

        $remarketingCustomers = $this->resolveRemarketingCustomerIds($customer);

        if ($remarketingCustomers->isEmpty()) {
            return response()->json([
                'success' => true,
                'orders' => [],
                'carts' => [],
                'stats' => null,
            ]);
        }

        $orders = Order::query()
            ->whereIn('customer_id', $remarketingCustomers)
            ->with('items')
            ->latest('placed_at')
            ->limit(10)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'status' => $o->status,
                'total' => $o->total,
                'currency' => $o->currency,
                'placed_at' => $o->placed_at?->toIso8601String(),
                'placed_at_human' => $o->placed_at?->diffForHumans(),
                'items_count' => $o->items->count(),
                'items' => $o->items->map(fn ($i) => [
                    'title' => $i->title,
                    'quantity' => $i->quantity,
                    'price' => $i->price,
                    'total' => $i->total,
                ]),
            ]);

        $carts = Cart::query()
            ->whereIn('customer_id', $remarketingCustomers)
            ->where('status', 'abandoned')
            ->latest('abandoned_at')
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'external_id' => $c->external_id,
                'total' => $c->total,
                'currency' => $c->currency,
                'items_count' => count($c->items ?? []),
                'items' => $c->items ?? [],
                'abandoned_at' => $c->abandoned_at?->toIso8601String(),
                'abandoned_at_human' => $c->abandoned_at?->diffForHumans(),
            ]);

        $stats = null;
        if ($orders->isNotEmpty()) {
            $stats = [
                'orders_count' => $orders->count(),
                'total_spent' => $orders->sum('total'),
                'currency' => $orders->first()['currency'] ?? 'EUR',
                'last_order_at' => $orders->first()['placed_at'] ?? null,
            ];
        }

        return response()->json([
            'success' => true,
            'orders' => $orders,
            'carts' => $carts,
            'stats' => $stats,
        ]);
    }

    /**
     * Cruce con Remarketing: criterio preferente el external_id del cliente en
     * la plataforma e-commerce (CustomerExternalId, exacto y estable frente a
     * cambios/reutilización de email); fallback el email en minúsculas, que era
     * el único criterio anterior.
     *
     * @return Collection<int, int>
     */
    private function resolveRemarketingCustomerIds(Customer $customer): Collection
    {
        $externalIds = $customer->externalIds()
            ->whereIn('platform', self::ECOMMERCE_PLATFORMS)
            ->pluck('external_id')
            ->filter()
            ->map(fn ($id) => (string) $id);

        if ($externalIds->isNotEmpty()) {
            $ids = RemarketingCustomer::query()
                ->whereIn('external_id', $externalIds->all())
                ->pluck('id');

            if ($ids->isNotEmpty()) {
                return $ids;
            }
        }

        if (! $customer->email) {
            return collect();
        }

        return RemarketingCustomer::query()
            ->where('email', strtolower($customer->email))
            ->pluck('id');
    }
}
