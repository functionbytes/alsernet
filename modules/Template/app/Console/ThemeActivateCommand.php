<?php

namespace Modules\Template\Console;

use Illuminate\Console\Command;
use Modules\Core\Models\Setting;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand('theme:activate', 'Activa un tema como plantilla activa del sitio')]
class ThemeActivateCommand extends Command
{
    public function handle(): int
    {
        $name = $this->argument('name');

        if (! $name) {
            $themes = $this->getAvailableThemes();
            if (empty($themes)) {
                $this->components->error('No se encontraron temas en platform/themes/.');

                return self::FAILURE;
            }
            $name = $this->components->choice('¿Qué tema deseas activar?', $themes);
        }

        if (! preg_match('/^[a-z0-9\-_]+$/i', $name)) {
            $this->components->error('El nombre solo puede contener caracteres alfanuméricos, guiones y guiones bajos.');

            return self::FAILURE;
        }

        $themePath = base_path('platform/themes/'.$name);
        if (! is_dir($themePath)) {
            $this->components->error("El tema [{$name}] no existe en platform/themes/.");

            return self::FAILURE;
        }

        Setting::set('template', $name);

        $this->components->info("Tema [{$name}] activado correctamente.");
        $this->components->info('Limpia la caché para aplicar los cambios: php artisan config:clear && php artisan view:clear');

        return self::SUCCESS;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Nombre del tema a activar');
    }

    protected function getAvailableThemes(): array
    {
        $path = base_path('platform/themes');
        if (! is_dir($path)) {
            return [];
        }

        return array_values(array_filter(
            scandir($path),
            fn ($item) => $item !== '.' && $item !== '..' && is_dir($path.'/'.$item)
        ));
    }
}
