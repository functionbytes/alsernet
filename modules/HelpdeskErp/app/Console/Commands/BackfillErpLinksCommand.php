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
                            {--id=* : Procesar sólo los customers con estos IDs (uno o varios)}
                            {--prestashop : Procesar sólo los customers vinculados a PrestaShop}
                            {--store-from-erp : En vez de buscar en Gestión, vincular con la tienda a los contactos ya vinculados a Gestión (CODIGO_INTERNET)}';

    protected $description = 'Recorre customers sin vínculo ERP y dispatcha LinkCustomerToErpJob (o ejecuta el linker en sync)';

    public function handle(ErpCustomerLinkerService $linker): int
    {
        $limit = (int) $this->option('limit');
        $chunk = max(1, (int) $this->option('chunk'));
        $sync = (bool) $this->option('sync');
        $ids = array_filter(array_map('intval', (array) $this->option('id')));

        if ($this->option('store-from-erp')) {
            return $this->storeFromErp($linker, $ids, $limit);
        }

        $query = Customer::on('helpdesk')
            ->whereDoesntHave('externalIds', fn ($q) => $q->where('platform', 'erp'))
            ->when(! empty($ids), fn ($q) => $q->whereIn('id', $ids))
            ->when((bool) $this->option('prestashop'), fn ($q) => $q->whereHas('externalIds', fn ($q) => $q->where('platform', 'prestashop')));

        $total = $query->count();
        $toProcess = $limit > 0 ? min($limit, $total) : $total;

        $this->info("Customers sin vínculo ERP: {$total}");
        $this->info('Modo: '.($sync ? 'sync (inline)' : 'queued (dispatch)'));
        $this->info("A procesar: {$toProcess}");

        if ($toProcess === 0) {
            return self::SUCCESS;
        }

        if (! $sync && ! config('helpdeskErp.auto_link', true)) {
            $this->warn('La vinculación automática está desactivada en «Ajustes de Gestión»: los jobs encolados no consultarán el ERP. Usa --sync.');

            return self::FAILURE;
        }

        $bar = $this->output->createProgressBar($toProcess);
        $bar->start();

        $stats = ['processed' => 0, 'linked' => 0, 'skipped' => 0, 'errors' => 0, 'via' => []];

        $query
            ->when($limit > 0, fn ($q) => $q->limit($limit))
            ->chunkById($chunk, function ($customers) use ($linker, $sync, $bar, &$stats) {
                foreach ($customers as $customer) {
                    try {
                        if ($sync) {
                            $erpId = $linker->linkCustomer($customer->load('externalIds'));
                            if ($erpId !== null) {
                                $stats['linked']++;
                                $via = $customer->externalIds()->where('platform', 'erp')->first()?->metadata['linked_via'] ?? 'desconocida';
                                $stats['via'][$via] = ($stats['via'][$via] ?? 0) + 1;
                            } else {
                                $stats['skipped']++;
                            }
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
            foreach ($stats['via'] as $via => $n) {
                $this->line("  · por {$via}: {$n}");
            }
            $this->info("Sin match en ERP: {$stats['skipped']}");
        } else {
            $this->info("Jobs encolados: {$stats['processed']} (queue helpdesk-erp)");
        }

        if ($stats['errors'] > 0) {
            $this->warn("Errores: {$stats['errors']}");
        }

        return self::SUCCESS;
    }

    /**
     * Gestión → tienda para contactos ya vinculados a Gestión y sin vínculo
     * con PrestaShop. Siempre inline: una llamada al manager por contacto.
     *
     * @param  int[]  $ids
     */
    private function storeFromErp(ErpCustomerLinkerService $linker, array $ids, int $limit): int
    {
        $query = Customer::on('helpdesk')
            ->whereHas('externalIds', fn ($q) => $q->where('platform', 'erp'))
            ->whereDoesntHave('externalIds', fn ($q) => $q->where('platform', 'prestashop'))
            ->when(! empty($ids), fn ($q) => $q->whereIn('id', $ids))
            ->when($limit > 0, fn ($q) => $q->limit($limit));

        $linked = 0;
        $processed = 0;

        foreach ($query->get() as $customer) {
            $erpId = $customer->externalIds()->where('platform', 'erp')->pluck('external_id')
                ->first(fn ($id) => is_numeric($id) && (int) $id > 0);
            if ($erpId === null) {
                continue;
            }
            $processed++;
            if ($linker->linkStoreFromErp($customer, (int) $erpId)) {
                $linked++;
            }
        }

        $this->info("Contactos revisados: {$processed} · vinculados con la tienda: {$linked}");

        return self::SUCCESS;
    }
}
