<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        return view('ecommerce::admin.dashboard', compact('stats'));
    }
}
