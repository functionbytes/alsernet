<?php

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Modules\Media\Models\MediaFile;

/**
 * Genera la versión `.webp` de cada imagen jpg/jpeg/png del Media.
 * El archivo original se conserva — el `.webp` vive con el mismo basename
 * para que RvMedia pueda emitir <picture><source srcset=".webp"> con
 * fallback al original sin cambiar las URLs almacenadas.
 *
 * WebP pesa 25-35 % menos que JPEG con calidad visual equivalente → ataca
 * directamente el mayor gasto de bytes del sitio (≈ 5.8 MB en PageSpeed).
 */
class ConvertToWebpCommand extends Command
{
    protected $signature = 'media:convert-webp
                            {--quality=82 : Calidad WebP (0-100)}
                            {--force : Regenerar aunque ya exista el .webp}
                            {--limit=0 : Procesar solo N archivos (0 = todos)}';

    protected $description = 'Convertir imágenes jpg/png del Media a .webp para mejorar PageSpeed';

    public function handle(): int
    {
        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            $this->error('Se requiere la extensión PHP gd o imagick.');

            return self::FAILURE;
        }

        $manager = ImageManager::gd();
        $quality = max(0, min(100, (int) $this->option('quality')));
        $force = (bool) $this->option('force');
        $limit = (int) $this->option('limit');

        $query = MediaFile::query()
            ->where('type', 'image')
            ->whereIn('mime_type', ['image/jpeg', 'image/jpg', 'image/png']);

        $total = $query->count();
        $this->info("Encontradas {$total} imágenes candidatas.");

        $count = 0;
        $converted = 0;
        $skipped = 0;
        $failed = 0;
        $bytesSaved = 0;

        $bar = $this->output->createProgressBar($limit > 0 ? min($limit, $total) : $total);
        $bar->start();

        $query->lazy()->each(function (MediaFile $file) use (&$count, &$converted, &$skipped, &$failed, &$bytesSaved, $manager, $quality, $force, $limit, $bar) {
            if ($limit > 0 && $count >= $limit) {
                return false;
            }
            $count++;
            $bar->advance();

            try {
                $diskName = $file->disk ?: config('filesystems.default');
                $disk = Storage::disk($diskName);
                $relative = ltrim((string) $file->url, '/');

                if (! $disk->exists($relative)) {
                    $failed++;

                    return;
                }

                $webpRelative = preg_replace('/\.(jpe?g|png)$/i', '.webp', $relative);
                if (! $force && $disk->exists($webpRelative)) {
                    $skipped++;

                    return;
                }

                $originalBytes = $disk->size($relative);
                $contents = $disk->get($relative);
                $image = $manager->read($contents);
                $webpData = (string) $image->toWebp($quality);
                $disk->put($webpRelative, $webpData);

                $bytesSaved += max(0, $originalBytes - strlen($webpData));
                $converted++;
            } catch (\Throwable $e) {
                $failed++;
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("✔ Convertidas: {$converted}");
        $this->line("  Omitidas (ya existían): {$skipped}");
        if ($failed) {
            $this->warn("  Fallidas: {$failed}");
        }
        $this->line('  Ahorro estimado: '.number_format($bytesSaved / 1024, 1).' KB');

        return self::SUCCESS;
    }
}
