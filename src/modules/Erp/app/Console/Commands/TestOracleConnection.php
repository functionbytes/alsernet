<?php

namespace Modules\Erp\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestOracleConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:test-oracle {--code-only : Suprimir salida detallada, devuelve solo el código de salida}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar conexión a Oracle Database y verificar configuración';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('code-only')) {
            return $this->runQuietCheck();
        }

        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║         Verificación de Conexión Oracle desde Laravel       ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Test 1: Verificar configuración
        $this->info('[1/5] Verificando configuración de base de datos...');

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver !== 'oracle') {
            $this->error("✗ La conexión por defecto no es Oracle (actual: {$driver})");
            $this->warn('  Actualiza DB_CONNECTION=oracle en tu archivo .env');

            return Command::FAILURE;
        }

        $this->line("  ✓ Driver: {$driver}");
        $this->line('  ✓ Host: '.config("database.connections.{$connection}.host"));
        $this->line('  ✓ Port: '.config("database.connections.{$connection}.port"));
        $this->line('  ✓ Database: '.config("database.connections.{$connection}.database"));
        $this->line('  ✓ Username: '.config("database.connections.{$connection}.username"));
        $this->newLine();

        // Test 2: Intentar obtener PDO
        $this->info('[2/5] Obteniendo instancia PDO...');

        try {
            $pdo = DB::connection('oracle')->getPdo();
            $this->line('  ✓ PDO obtenida exitosamente');
            $this->line('  ✓ Driver PDO: '.$pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
            $this->line('  ✓ Versión cliente: '.$pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION));
        } catch (Exception $e) {
            $this->error('  ✗ Error al obtener PDO: '.$e->getMessage());
            $this->newLine();
            $this->showTroubleshooting($e);

            return Command::FAILURE;
        }

        $this->newLine();

        // Test 3: Probar consulta simple
        $this->info('[3/5] Ejecutando consulta de prueba (SELECT FROM dual)...');

        try {
            $result = DB::connection('oracle')->select('SELECT SYSDATE as fecha FROM dual');
            $this->line('  ✓ Consulta ejecutada exitosamente');
            $this->line('  ✓ Fecha del servidor: '.$result[0]->FECHA);
        } catch (Exception $e) {
            $this->error('  ✗ Error en consulta: '.$e->getMessage());
            $this->newLine();
            $this->showTroubleshooting($e);

            return Command::FAILURE;
        }

        $this->newLine();

        // Test 4: Obtener información del servidor
        $this->info('[4/5] Obteniendo información del servidor Oracle...');

        try {
            // Usuario actual
            $user = DB::connection('oracle')->select('SELECT USER as username FROM dual');
            $this->line('  ✓ Usuario conectado: '.$user[0]->USERNAME);

            // Versión de Oracle
            $version = DB::connection('oracle')->select("SELECT * FROM v\$version WHERE banner LIKE 'Oracle%'");
            if (! empty($version)) {
                $this->line('  ✓ Versión Oracle: '.$version[0]->BANNER);
            }

            // Nombre de la base de datos
            $dbName = DB::connection('oracle')->select('SELECT name FROM v$database');
            if (! empty($dbName)) {
                $this->line('  ✓ Nombre de BD: '.$dbName[0]->NAME);
            }

            // Instancia
            $instance = DB::connection('oracle')->select('SELECT instance_name FROM v$instance');
            if (! empty($instance)) {
                $this->line('  ✓ Instancia: '.$instance[0]->INSTANCE_NAME);
            }

        } catch (Exception $e) {
            $this->warn('  ⚠ No se pudo obtener toda la información del servidor');
            $this->line('    (esto es normal si el usuario no tiene privilegios sobre vistas v$)');
        }

        $this->newLine();

        // Test 5: Verificar tablas de usuario
        $this->info('[5/5] Listando tablas del usuario...');

        try {
            $tables = DB::connection('oracle')->select('SELECT table_name FROM user_tables ORDER BY table_name');

            if (empty($tables)) {
                $this->line('  ⚠ No hay tablas creadas aún (esto es normal en una instalación nueva)');
                $this->line('    Ejecuta: php artisan migrate');
            } else {
                $this->line('  ✓ Tablas encontradas: '.count($tables));
                $this->newLine();

                $tableNames = array_map(fn ($t) => $t->TABLE_NAME, $tables);
                $this->table(['Tabla'], array_map(fn ($name) => [$name], array_slice($tableNames, 0, 10)));

                if (count($tableNames) > 10) {
                    $this->line('  ... y '.(count($tableNames) - 10).' más');
                }
            }
        } catch (Exception $e) {
            $this->error('  ✗ Error al listar tablas: '.$e->getMessage());
        }

        $this->newLine();

        // Resumen final
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                  ✓ CONEXIÓN EXITOSA                          ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->line('<fg=green>¡Felicidades!</> Tu aplicación Laravel está correctamente conectada a Oracle.');
        $this->newLine();

        $this->line('Próximos pasos:');
        $this->line('  1. Crear modelos: <fg=cyan>php artisan make:model NombreModelo -m</>');
        $this->line('  2. Ejecutar migraciones: <fg=cyan>php artisan migrate</>');
        $this->line('  3. Probar en tinker: <fg=cyan>php artisan tinker</>');
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Quick health check used by monitoring/CI: PDO + SELECT FROM dual, no output.
     */
    protected function runQuietCheck(): int
    {
        try {
            DB::connection('oracle')->select('SELECT 1 FROM dual');

            return Command::SUCCESS;
        } catch (Exception) {
            return Command::FAILURE;
        }
    }

    /**
     * Mostrar sugerencias de solución de problemas
     */
    protected function showTroubleshooting(Exception $e): void
    {
        $this->warn('═══ Solución de Problemas ═══');
        $this->newLine();

        $message = $e->getMessage();

        if (str_contains($message, 'could not find driver')) {
            $this->line('• La extensión PHP OCI8 no está instalada o habilitada');
            $this->line('  Instalar: <fg=cyan>pecl install oci8</>');
            $this->line('  Habilitar: agregar <fg=cyan>extension=oci8.so</> a php.ini');
            $this->line('  Verificar: <fg=cyan>php -m | grep oci8</>');
        }

        if (str_contains($message, 'OCIEnvNlsCreate')) {
            $this->line('• Oracle Instant Client no está correctamente configurado');
            $this->line('  Verificar variables: <fg=cyan>echo $ORACLE_HOME</>');
            $this->line('  Verificar librerías: <fg=cyan>ls -la $ORACLE_HOME/libclntsh.*</>');
        }

        if (str_contains($message, 'ORA-12154') || str_contains($message, 'TNS')) {
            $this->line('• Error de conexión TNS');
            $this->line('  Verificar host y puerto en .env');
            $this->line('  Probar conectividad: <fg=cyan>telnet '.config('database.connections.oracle.host').' '.config('database.connections.oracle.port').'</>');
        }

        if (str_contains($message, 'ORA-12505')) {
            $this->line('• SID o Service Name incorrecto');
            $this->line('  Verificar DB_DATABASE y DB_SERVICE_NAME en .env');
            $this->line('  Contactar al DBA para obtener el SID/Service Name correcto');
        }

        if (str_contains($message, 'ORA-01017')) {
            $this->line('• Usuario o contraseña incorrectos');
            $this->line('  Verificar DB_USERNAME y DB_PASSWORD en .env');
        }

        $this->newLine();
        $this->line('Para más ayuda, consulta: <fg=cyan>ORACLE-SETUP.md</>');
    }
}
