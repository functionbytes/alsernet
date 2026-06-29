<?php

namespace Modules\Helpdesk\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\Compliance\GdprDeletionService;

class PurgeOldGdprDeletes extends Command
{
    protected $signature = 'helpdesk:purge-old-gdpr-deletes
                            {--days=90 : Number of days after soft-delete before hard purge}';

    protected $description = 'Hard-delete customers that were soft-deleted (GDPR anonymised) more than N days ago';

    public function __construct(private readonly GdprDeletionService $deletionService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $retentionDays = (int) $this->option('days');
        $cutoff = now()->subDays($retentionDays);

        $customers = Customer::query()
            ->onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->get();

        if ($customers->isEmpty()) {
            $this->info("No hay clientes GDPR pendientes de purga (>{$retentionDays} días).");

            return self::SUCCESS;
        }

        $totalDeleted = 0;

        foreach ($customers as $customer) {
            try {
                $result = $this->deletionService->deleteCustomer($customer, hard: true);
                $totalDeleted += $result['deleted'];

                $this->line("  Purgado customer ID {$customer->id} ({$result['deleted']} registros eliminados).");
            } catch (\Throwable $e) {
                Log::error('GDPR purge failed for customer', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);

                $this->error("  Error al purgar customer ID {$customer->id}: {$e->getMessage()}");
            }
        }

        $this->info("Purga GDPR completada. Clientes: {$customers->count()}, registros eliminados: {$totalDeleted}.");

        return self::SUCCESS;
    }
}
