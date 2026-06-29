<?php

namespace Modules\Template\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ThemeLinkCommand extends Command
{
    protected $signature = 'theme:link';

    protected $description = 'Crea symlinks de los assets de todos los temas en public/themes/';

    public function handle(): int
    {
        $themesPath = base_path('platform/themes');
        $publicThemesPath = public_path('themes');

        if (! is_dir($themesPath)) {
            $this->error('No existe la carpeta platform/themes/');

            return self::FAILURE;
        }

        File::ensureDirectoryExists($publicThemesPath);

        $folders = array_diff(scandir($themesPath), ['.', '..']);
        $linked = 0;

        foreach ($folders as $folder) {
            $themePublicPath = $themesPath.'/'.$folder.'/public';
            $symlink = $publicThemesPath.'/'.$folder;

            if (! is_dir($themePublicPath)) {
                continue;
            }

            if (is_link($symlink)) {
                $this->line("  <comment>Ya existe:</comment> themes/{$folder}");

                continue;
            }

            if (is_dir($symlink)) {
                $this->line("  <comment>Ya existe (directorio):</comment> themes/{$folder}");

                continue;
            }

            symlink($themePublicPath, $symlink);
            $this->line("  <info>Enlazado:</info> themes/{$folder} → platform/themes/{$folder}/public");
            $linked++;
        }

        if ($linked > 0) {
            $this->info("Se crearon {$linked} symlink(s) correctamente.");
        } else {
            $this->info('No se crearon nuevos symlinks.');
        }

        return self::SUCCESS;
    }
}
