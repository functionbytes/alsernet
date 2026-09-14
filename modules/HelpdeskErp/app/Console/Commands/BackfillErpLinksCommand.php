<?php

namespace Modules\HelpdeskErp\Console\Commands;

use Illuminate\Console\Command;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\HelpdeskErp\Services\ErpCustomerLinkerService;

class BackfillErpLinksCommand extends Command
{
    protected $signature = 'helpdeskerp:backfill-links
                            {--limit=0 : Máximo de customers a procesar (0 = sin límite)}
                            {--chunk=100 : Tamaño de chunk para iterar customers}
                            {--sync : Ejecutar el linker inline en lugar de encolar el job}
                            {--id=* : Procesar sólo los customers con estos IDs (uno o varios)}';

    protected $description = 'Recorre customers sin vínculo ERP y dispatcha LinkCustomerToErpJob (o ejecuta el linker en sync)';

    public function handle(ErpCustomerLinkerService $linker): int
    {
        $limit = (int) $this->option('limit');
        $chunk = max(1, (int) $this->option('chunk'));
        $sync = (bool) $this->option('sync');
        $ids = array_filter(array_map('intval', (array) $this->option('id')));

        $query = Customer::on('helpdesk')
            ->whereDoesntHave('externalIds', fn ($q) => $q->where('platform', 'erp'))
            ->when(! empty($ids), fn ($q) => $q->whereIn('id', $ids));

        $total = $query->count();
        $toProcess = $limit > 0 ? min($limit, $total) : $total;

        $this->info("Customers sin vínculo ERP: {$total}");
        $this->info('Modo: '.($sync ? 'sync (inline)' : 'queued (dispatch)'));
        $this->info("A procesar: {$toProcess}");

        if ($toProcess === 0) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($toProcess);
        $bar->start();

        $stats = ['processed' => 0, 'linked' => 0, 'skipped' => 0, 'errors' => 0];

        $query
            ->when($limit > 0, fn ($q) => $q->limit($limit))
            ->chunkById($chunk, function ($customers) use ($linker, $sync, $bar, &$stats) {
                foreach ($customers as $customer) {
                    try {
                        if ($sync) {
                            $erpId = $linker->linkCustomer($customer->load('externalIds'));
                            $erpId !== null ? $stats['linked']++ : $stats['skipped']++;
                        } else {
                            LinkCustomerToErpJob::dispatch($customer->id);
                            $stats['processed']++;
                        }
                    } catch (\Throwable $e) {
                        $stats['errors']++;
                        $this->newLine();
                        $this->warn("Customer {$customer->id}: {$e->getMessage()}");
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        if ($sync) {
            $this->info("Vinculados: {$stats['linked']}");
            $this->info("Sin match en ERP: {$stats['skipped']}");
        } else {
            $this->info("Jobs encolados: {$stats['processed']} (queue helpdesk-erp)");
        }

        if ($stats['errors'] > 0) {
            $this->warn("Errores: {$stats['errors']}");
        }

        return self::SUCCESS;
    }
}
