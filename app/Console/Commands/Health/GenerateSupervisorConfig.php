<?php

namespace App\Console\Commands\Health;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSupervisorConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:supervisor-config
                            {--queue= : Queue connection to use (default: config value)}
                            {--workers=3 : Number of worker processes}
                            {--tries=3 : Number of times to attempt a job}
                            {--timeout=300 : Seconds a job can run before timing out}
                            {--force : Overwrite existing config file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar configuración de Supervisor para Laravel Queue Workers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 Generando configuración de Supervisor...');
        $this->newLine();

        // Get configuration values
        $appName = str_replace(' ', '-', strtolower(config('app.name', 'laravel')));
        $basePath = base_path();
        $phpPath = PHP_BINARY;
        $queueConnection = $this->option('queue') ?? config('queue.default');
        $numProcs = $this->option('workers');
        $maxTries = $this->option('tries');
        $timeout = $this->option('timeout');
        $user = get_current_user();

        // Determine storage path for config (dentro del módulo Health)
        $configPath = base_path('modules/Health/storage/supervisor');
        $configFile = "{$configPath}/{$appName}-worker.conf";

        // Create directory if it doesn't exist
        if (! File::exists($configPath)) {
            File::makeDirectory($configPath, 0755, true);
            $this->info("✅ Directorio creado: {$configPath}");
        }

        // Check if file exists
        if (File::exists($configFile) && ! $this->option('force')) {
            if (! $this->confirm('El archivo de configuración ya existe. ¿Deseas sobrescribirlo?', false)) {
                $this->warn('❌ Operación cancelada.');

                return self::FAILURE;
            }
        }

        // Generate config content
        $config = $this->generateConfig([
            'app_name' => $appName,
            'base_path' => $basePath,
            'php_path' => $phpPath,
            'queue_connection' => $queueConnection,
            'num_procs' => $numProcs,
            'max_tries' => $maxTries,
            'timeout' => $timeout,
            'user' => $user,
        ]);

        // Write config file
        File::put($configFile, $config);

        $this->newLine();
        $this->info('✅ Configuración de Supervisor generada exitosamente!');
        $this->newLine();

        // Display instructions
        $this->displayInstructions($configFile, $appName);

        return self::SUCCESS;
    }

    /**
     * Generate Supervisor configuration content
     */
    protected function generateConfig(array $params): string
    {
        return <<<EOF
[program:{$params['app_name']}-worker]
process_name=%(program_name)s_%(process_num)02d
command={$params['php_path']} {$params['base_path']}/artisan queue:work {$params['queue_connection']} --sleep=3 --tries={$params['max_tries']} --max-time={$params['timeout']} --timeout={$params['timeout']}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user={$params['user']}
numprocs={$params['num_procs']}
redirect_stderr=true
stdout_logfile={$params['base_path']}/storage/logs/worker.log
stopwaitsecs=3600
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
EOF;
    }

    /**
     * Display installation and usage instructions
     */
    protected function displayInstructions(string $configFile, string $appName): void
    {
        $this->line('📋 <fg=cyan>Instrucciones de Instalación:</>');
        $this->newLine();

        $this->line('<fg=yellow>1. Instalar Supervisor (si no está instalado):</>');
        $this->line('   Ubuntu/Debian:');
        $this->line('   <fg=green>sudo apt-get install supervisor</>');
        $this->newLine();
        $this->line('   macOS (Homebrew):');
        $this->line('   <fg=green>brew install supervisor</>');
        $this->line('   <fg=green>brew services start supervisor</>');
        $this->newLine();

        $this->line('<fg=yellow>2. Copiar el archivo de configuración:</>');
        $this->line("   <fg=green>sudo cp {$configFile} /etc/supervisor/conf.d/{$appName}-worker.conf</>");
        $this->newLine();

        $this->line('<fg=yellow>3. Recargar Supervisor:</>');
        $this->line('   <fg=green>sudo supervisorctl reread</>');
        $this->line('   <fg=green>sudo supervisorctl update</>');
        $this->line('   <fg=green>sudo supervisorctl start '.$appName.'-worker:*</>');
        $this->newLine();

        $this->line('<fg=yellow>4. Verificar estado:</>');
        $this->line('   <fg=green>sudo supervisorctl status</>');
        $this->newLine();

        $this->line('📁 <fg=cyan>Archivo generado:</>');
        $this->line("   {$configFile}");
        $this->newLine();

        $this->line('🎯 <fg=cyan>Comandos útiles de Supervisor:</>');
        $this->table(
            ['Comando', 'Descripción'],
            [
                ['sudo supervisorctl status', 'Ver estado de todos los procesos'],
                ["sudo supervisorctl restart {$appName}-worker:*", 'Reiniciar todos los workers'],
                ["sudo supervisorctl stop {$appName}-worker:*", 'Detener todos los workers'],
                ['sudo supervisorctl tail -f '.$appName.'-worker:* stdout', 'Ver logs en tiempo real'],
            ]
        );
        $this->newLine();

        $this->line('💡 <fg=cyan>Tip:</> Después de desplegar código nuevo, reinicia los workers:');
        $this->line('   <fg=green>php artisan queue:restart</>');
        $this->line('   (Los workers se reiniciarán gracefully al terminar el job actual)');
    }
}
