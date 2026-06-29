<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\CampaignSendingServers\Models\SendingServer;

/**
 * Bootstrap idempotente del módulo Campaign + CampaignSendingServers.
 *
 *   php artisan campaign:install
 *
 * Ejecuta:
 *   1. composer dump-autoload (si --dump)
 *   2. migrate (sin --fresh)
 *   3. permissions:sync si el comando existe (módulo Role)
 *   4. crea SendingServer Sendmail por defecto si no hay ninguno
 *   5. crea MailList "Default" si no hay ninguna
 *   6. muestra siguientes pasos
 */
class InstallCommand extends Command
{
    protected $signature = 'campaign:install
                            {--fresh : DESTRUCTIVO: migrate:fresh en vez de migrate (¡borra datos!)}
                            {--dump : Ejecuta composer dump-autoload antes de migrar}';

    protected $description = 'Instala/actualiza el módulo Campaign con configuración por defecto.';

    public function handle(): int
    {
        $this->info('🚀 Instalando módulo Campaign + CampaignSendingServers');
        $this->newLine();

        if ($this->option('dump')) {
            $this->info('1. composer dump-autoload…');
            shell_exec('cd '.escapeshellarg(base_path()).' && composer dump-autoload --no-interaction 2>&1');
            $this->line('   ✓ done');
        }

        // Migrate
        if ($this->option('fresh')) {
            if (! $this->confirm('⚠️  --fresh borra TODA la BD. ¿Continuar?', false)) {
                $this->error('Abortado.');

                return self::FAILURE;
            }
            $this->info('2. migrate:fresh…');
            Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            $this->info('2. migrate…');
            Artisan::call('migrate', ['--force' => true]);
        }
        $this->line('   ✓ '.trim(Artisan::output()));

        // Sync permisos si existe el comando (módulo Role)
        if (in_array('permissions:sync', array_keys(Artisan::all()), true)) {
            $this->info('3. permissions:sync…');
            Artisan::call('permissions:sync');
            $this->line('   ✓ done');
        } else {
            $this->warn('   permissions:sync no disponible — añade los permisos manualmente desde Role/config/permissions.php.');
        }

        // SendingServer por defecto
        $this->info('4. SendingServer por defecto…');
        if (SendingServer::count() === 0) {
            SendingServer::create([
                'name' => 'Sendmail local',
                'type' => SendingServer::TYPE_SENDMAIL,
                'sendmail_path' => '/usr/sbin/sendmail',
                'default_from_email' => 'noreply@'.parse_url((string) config('app.url'), PHP_URL_HOST),
                'status' => SendingServer::STATUS_ACTIVE,
                'quota_value' => 60,
                'quota_base' => 1,
                'quota_unit' => 'minute',
            ]);
            $this->line('   ✓ creado SendingServerSendmail "Sendmail local"');
        } else {
            $this->line('   ✓ ya hay '.SendingServer::count().' servidor(es) configurados, skip.');
        }

        // MailList por defecto
        $this->info('5. MailList por defecto…');
        if (CampaignMaillist::count() === 0) {
            $list = CampaignMaillist::create([
                'name' => 'Default',
                'description' => 'Lista por defecto creada por campaign:install',
                'from_email' => 'noreply@'.parse_url((string) config('app.url'), PHP_URL_HOST),
                'from_name' => config('app.name'),
                'subscribe_confirmation' => 1,
                'send_welcome_email' => 0,
                'unsubscribe_notification' => 0,
            ]);
            $this->line('   ✓ creada MailList "Default" (uid '.$list->uid.')');
        } else {
            $this->line('   ✓ ya hay '.CampaignMaillist::count().' lista(s) configuradas, skip.');
        }

        // Schedule list
        $this->newLine();
        $this->info('✅ Instalación completada.');
        $this->line('');
        $this->line('Siguientes pasos:');
        $this->line('  1. Configura un SendingServer real en /panel/sending-servers');
        $this->line('  2. Asocia ese servidor a tu(s) lista(s)');
        $this->line('  3. Asegúrate de tener `* * * * * php artisan schedule:run` en el cron del sistema');
        $this->line('  4. Lanza un worker: `php artisan queue:work` (default + webhooks)');
        $this->line('  5. Crea tu primera campaña en /panel/campaign');
        $this->newLine();

        return self::SUCCESS;
    }
}
