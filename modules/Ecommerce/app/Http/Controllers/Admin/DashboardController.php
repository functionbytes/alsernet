<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'products_count' => Product::query()->count(),
            'orders_count' => Order::query()->count(),
            'customers_count' => Customer::query()->count(),
            'total_revenue' => Order::query()->where('payment_status', 'paid')->sum('total'),
            'recent_orders' => Order::query()->latest()->limit(10)->get(),
            'low_stock_products' => Product::query()->where('with_storehouse_management', true)->where('quantity', '<=', 5)->limit(10)->get(),
        ];

        return view('ecommerce::dashboard.index', compact('stats'));
    }

    public function quickSearch(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['products' => [], 'customers' => [], 'orders' => []]);
        }

        $products = Product::query()
            ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"))
            ->limit(5)
            ->get(['id', 'name', 'sku'])
            ->map(fn ($p) => [
                'label' => $p->name.($p->sku ? " ({$p->sku})" : ''),
                'url' => route('ecommerce.products.edit', $p->id),
            ]);

        $customers = Customer::query()
            ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
            ->limit(5)
            ->get(['id', 'name', 'email'])
            ->map(fn ($c) => [
                'label' => $c->name.' — '.$c->email,
                'url' => route('ecommerce.customers.edit', $c->id),
            ]);

        $orders = Order::query()
            ->where('code', 'like', "%{$q}%")
            ->limit(5)
            ->get(['id', 'code', 'total'])
            ->map(fn ($o) => [
                'label' => "Orden {$o->code} — \${$o->total}",
                'url' => route('ecommerce.orders.show', $o->id),
            ]);

        return response()->json([
            'products' => $products,
            'customers' => $customers,
            'orders' => $orders,
        ]);
    }
}
