<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Supports\InvoiceHelper;

class GenerateInvoiceForOrders extends Command
{
    protected $signature = 'ecommerce:generate-invoices';

    protected $description = 'Generate missing invoices for completed orders';

    public function handle(): void
    {
        $orders = Order::query()
            ->where('status', 'completed')
            ->doesntHave('invoice')
            ->get();

        foreach ($orders as $order) {
            InvoiceHelper::store($order);
        }

        $this->info("Generated {$orders->count()} invoices.");
    }
}
