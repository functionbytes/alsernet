<?php

namespace Modules\Core\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Models\Setting;

class ConfigureMaintenanceTools extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:configure {--composer= : Ruta al ejecutable de Composer} {--php= : Ruta al ejecutable de PHP}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura las rutas de Composer y PHP para el mantenimiento del sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $composerPath = $this->option('composer');
        $phpPath = $this->option('php');

        if ($composerPath) {
            if (! file_exists($composerPath)) {
                $this->error("❌ Composer no encontrado en: {$composerPath}");

                return 1;
            }
            Setting::set('composer_path', $composerPath);
            $this->info("✓ Ruta de Composer guardada: {$composerPath}");
        }

        if ($phpPath) {
            if (! file_exists($phpPath)) {
                $this->error("❌ PHP no encontrado en: {$phpPath}");

                return 1;
            }
            Setting::set('php_path', $phpPath);
            $this->info("✓ Ruta de PHP guardada: {$phpPath}");
        }

        if (! $composerPath && ! $phpPath) {
            $this->info('Configuración actual:');
            $this->table(
                ['Setting', 'Valor'],
                [
                    ['composer_path', Setting::get('composer_path') ?: '(auto-detectar)'],
                    ['php_path', Setting::get('php_path') ?: '(usar PHP actual)'],
                ]
            );
        }

        return 0;
    }
}
