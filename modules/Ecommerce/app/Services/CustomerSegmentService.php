<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Ecommerce\Models\Customer;

class CustomerSegmentService
{
    public function getSegment(string $segment): Builder
    {
        return match ($segment) {
            'all' => Customer::query()->where('status', 'active'),
            'vip' => $this->vipCustomers(),
            'inactive' => $this->inactiveCustomers(),
            'new' => $this->newCustomers(),
            'recent_buyers' => $this->recentBuyers(),
            default => Customer::query()->whereRaw('1=0'),
        };
    }

    public function vipCustomers(): Builder
    {
        return Customer::query()
            ->whereHas('orders', fn ($q) => $q->where('payment_status', 'paid'))
            ->withSum(['orders as total_spent' => fn ($q) => $q->where('payment_status', 'paid')], 'total')
            ->having('total_spent', '>=', 1000);
    }

    public function inactiveCustomers(): Builder
    {
        return Customer::query()
            ->whereDoesntHave('orders', fn ($q) => $q->where('created_at', '>=', now()->subDays(60)));
    }

    public function newCustomers(): Builder
    {
        return Customer::query()->where('created_at', '>=', now()->subDays(30));
    }

    public function recentBuyers(): Builder
    {
        return Customer::query()
            ->whereHas('orders', fn ($q) => $q
                ->where('created_at', '>=', now()->subDays(7))
                ->where('payment_status', 'paid')
            );
    }

    public function countSegment(string $segment): int
    {
        return $this->getSegment($segment)->count();
    }
}
