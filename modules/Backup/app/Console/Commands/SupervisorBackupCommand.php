<?php

namespace Modules\Backup\Console\Commands;

use App\Services\SupervisorService;
use Illuminate\Console\Command;

class SupervisorBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervisor:backup {name? : Nombre del backup} {--environment=dev : Ambiente (dev, prod, staging)} {--description= : Descripción opcional}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear un backup manual de las configuraciones de Supervisor';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');

        if (! $name) {
            $name = 'Backup '.now()->format('Y-m-d H:i:s');
        }

        $environment = $this->option('environment');
        $description = $this->option('description');

        if (! in_array($environment, ['dev', 'prod', 'staging'])) {
            $this->error('Ambiente inválido. Usar: dev, prod o staging');

            return 1;
        }

        $this->info("Creando backup: {$name}");
        $this->info("Ambiente: {$environment}");

        try {
            $result = SupervisorService::createBackup($name, $description, $environment);

            if ($result['success']) {
                $this->info("✅ Backup creado exitosamente (ID: {$result['backup_id']})");

                return 0;
            } else {
                $this->error("❌ Error al crear backup: {$result['error']}");

                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Excepción: {$e->getMessage()}");

            return 1;
        }
    }
}
