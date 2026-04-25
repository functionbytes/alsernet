<?php

namespace Modules\EcommercePayment\Console\Commands;

use Illuminate\Console\Command;
use Modules\EcommercePayment\Enums\PaymentStatus;
use Modules\EcommercePayment\Models\Payment;

class CleanupPendingPaymentsCommand extends Command
{
    protected $signature = 'ecommerce-payment:cleanup-pending
                            {--hours=24 : Hours after which pending payments are considered expired}';

    protected $description = 'Delete pending payments that have not been completed after a given number of hours';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $payments = Payment::query()
            ->where('status', PaymentStatus::PENDING)
            ->where('created_at', '<', $cutoff)
            ->get();

        $count = $payments->count();

        if ($count === 0) {
            $this->info('No pending payments to clean up.');

            return self::SUCCESS;
        }

        foreach ($payments as $payment) {
            $payment->delete();
        }

        $this->info("Deleted {$count} pending payment(s) older than {$hours} hours.");

        return self::SUCCESS;
    }
}
