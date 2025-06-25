<?php

namespace App\Console\Commands;

use App\Services\Return\WarrantyService;
use Illuminate\Console\Command;

class ProcessWarranties extends Command
{
    protected $signature = 'warranties:process {--sync-manufacturers : Sincronizar con fabricantes}';
    protected $description = 'Procesar garantías: notificaciones, estados, sincronización';

    protected $warrantyService;

    public function __construct(WarrantyService $warrantyService)
    {
        parent::__construct();
        $this->warrantyService = $warrantyService;
    }

    public function handle()
    {
        $this->info('Iniciando procesamiento de garantías...');

        // Procesar notificaciones y estados
        $results = $this->warrantyService->processWarrantyNotifications();

        $this->info("Notificaciones de vencimiento enviadas: {$results['expiring_notifications']}");
        $this->info("Garantías marcadas como expiradas: {$results['expired_warranties']}");

        // Sincronizar con fabricantes si se solicita
        if ($this->option('sync-manufacturers')) {
            $this->info('Sincronizando con fabricantes...');
            $syncResults = $this->warrantyService->syncWithManufacturers();

            $successful = collect($syncResults)->where('status', 'success')->count();
            $failed = collect($syncResults)->where('status', 'error')->count();

            $this->info("Sincronizaciones exitosas: {$successful}");
            if ($failed > 0) {
                $this->warn("Sincronizaciones fallidas: {$failed}");
            }
        }

        $this->info('Procesamiento de garantías completado.');
        return 0;
    }
}
