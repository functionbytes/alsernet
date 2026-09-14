<?php

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Modules\Media\Models\MediaFile;

/**
 * Generate CSS sprite sheets from small images (icons) in a media folder.
 *
 * Usage:
 *   php artisan media:generate-sprites --folder=3 --output=sprites/icons
 */
class GenerateSpriteSheetsCommand extends Command
{
    protected $signature = 'media:generate-sprites
        {--folder= : MediaFolder ID containing the icons}
        {--tag= : MediaTag name to filter icons}
        {--output=sprites/icons : Relative path inside the media disk}
        {--size=32 : Icon size in pixels (square)}';

    protected $description = 'Generate a CSS sprite sheet from small images';

    public function handle(): int
    {
        $folderId = $this->option('folder');
        $tagName = $this->option('tag');
        $outputDir = $this->option('output');
        $iconSize = (int) $this->option('size');

        $query = MediaFile::query()
            ->where('type', 'image')
            ->whereIn('mime_type', ['image/png', 'image/webp', 'image/avif', 'image/jpeg', 'image/gif']);

        if ($folderId) {
            $query->where('folder_id', $folderId);
        }

        if ($tagName) {
            $query->whereHas('tags', fn ($q) => $q->where('name', $tagName));
        }

        $files = $query->get();

        if ($files->isEmpty()) {
            $this->warn('No se encontraron imágenes para el sprite sheet.');

            return self::FAILURE;
        }

        $disk = config('media.default_disk', 'media');
        $driver = extension_loaded('imagick') ? new ImagickDriver : new GdDriver;
        $manager = new ImageManager($driver);

        $iconSize = max(16, min(128, $iconSize));
        $cols = ceil(sqrt($files->count()));
        $rows = ceil($files->count() / $cols);
        $sheetWidth = $cols * $iconSize;
        $sheetHeight = $rows * $iconSize;

        $canvas = $manager->create($sheetWidth, $sheetHeight)->fill('00000000');
        $css = ".sprite-icon { display:inline-block; background-image:url('{$outputDir}.webp'); background-repeat:no-repeat; background-size:{$sheetWidth}px {$sheetHeight}px; width:{$iconSize}px; height:{$iconSize}px; }\n";

        $index = 0;

        foreach ($files as $file) {
            if (! Storage::disk($disk)->exists($file->url)) {
                continue;
            }

            try {
                $icon = $manager->read(Storage::disk($disk)->get($file->url));
                $icon = $icon->cover($iconSize, $iconSize);

                $col = $index % $cols;
                $row = (int) floor($index / $cols);
                $x = $col * $iconSize;
                $y = $row * $iconSize;

                $canvas->place($icon, 'top-left', $x, $y);

                $className = 'sprite-icon-'.preg_replace('/[^a-z0-9_-]/i', '-', pathinfo($file->name, PATHINFO_FILENAME));
                $css .= ".{$className} { background-position:-{$x}px -{$y}px; }\n";

                $index++;
            } catch (\Throwable $e) {
                $this->warn("Saltando {$file->name}: {$e->getMessage()}");
            }
        }

        if ($index === 0) {
            $this->error('No se pudo procesar ninguna imagen.');

            return self::FAILURE;
        }

        // Save sprite sheet as WebP
        $sheetPath = $outputDir.'.webp';
        Storage::disk($disk)->put($sheetPath, (string) $canvas->toWebp(85));

        // Save CSS
        $cssPath = $outputDir.'.css';
        Storage::disk($disk)->put($cssPath, $css);

        $this->info("Sprite sheet generado: {$sheetPath}");
        $this->info("CSS generado: {$cssPath}");
        $this->info("Iconos incluidos: {$index}");
        $this->info("Dimensiones: {$sheetWidth}x{$sheetHeight}px");

        return self::SUCCESS;
    }
}
