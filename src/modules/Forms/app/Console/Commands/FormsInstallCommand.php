<?php

namespace Modules\Forms\Console\Commands;

use Illuminate\Console\Command;
use Modules\Forms\Database\Seeders\FormsDemoSeeder;
use Modules\Forms\Database\Seeders\FormsPermissionsSeeder;

class FormsInstallCommand extends Command
{
    protected $signature = 'forms:install';

    protected $description = 'Instala el módulo Forms: seedea permisos y datos demo';

    public function handle(): int
    {
        $this->info('Instalando módulo Forms...');

        $this->call('db:seed', ['--class' => FormsPermissionsSeeder::class]);
        $this->info('✓ Permisos creados');

        $this->call('db:seed', ['--class' => FormsDemoSeeder::class]);
        $this->info('✓ Formulario demo creado');

        $this->info('Módulo Forms instalado correctamente.');

        return self::SUCCESS;
    }
}
