<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Modules\Ecommerce\Services\CustomerSegmentService;

class SendWinBackCampaignCommand extends Command
{
    protected $signature = 'ecommerce:send-winback';

    protected $description = 'Envía email de reconquista a clientes inactivos (60+ días sin comprar)';

    public function handle(CustomerSegmentService $segments): int
    {
        $sent = 0;

        $segments->inactiveCustomers()
            ->whereDoesntHave('orders', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
            ->select('id', 'name', 'email')
            ->chunk(100, function ($customers) use (&$sent) {
                foreach ($customers as $customer) {
                    $html = view('ecommerce::emails.winback', ['customer' => $customer])->render();
                    Mail::html($html, fn ($m) => $m->to($customer->email)->subject('Te extrañamos — 15% OFF para volver'));
                    $sent++;
                }
            });

        $this->info("Win-back emails enviados: {$sent}");

        return self::SUCCESS;
    }
}
