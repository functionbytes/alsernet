<?php

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Modules\Media\Models\MediaFile;

/**
 * Genera variantes responsive de cada imagen del Media: 480 / 768 / 1024 /
 * 1920 px de ancho. Los archivos nuevos se llaman `foo-480w.webp`,
 * `foo-768w.webp`, etc. y conviven con el original y su WebP.
 *
 * Combinado con `RvMedia::image()` emitiendo `srcset`, esto hace que el
 * navegador descargue solo el tamaño que necesita. Un mobile que antes
 * bajaba 2 MB a full-HD ahora baja ~200 KB a 480px → ahorra el último gran
 * bloque que queda de imágenes en PageSpeed.
 */
class GenerateSrcsetCommand extends Command
{
    protected $signature = 'media:generate-srcset
                            {--widths=480,768,1024,1920 : Anchos de las variantes, CSV}
                            {--quality=82 : Calidad WebP (0-100)}
                            {--force : Regenerar aunque ya exista la variante}
                            {--limit=0 : Procesar solo N archivos (0 = todos)}';

    protected $description = 'Generar variantes responsive (-480w, -768w, -1024w, -1920w) de las imágenes del Media';

    public function handle(): int
    {
        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            $this->error('Se requiere gd o imagick.');

            return self::FAILURE;
        }

        $widths = array_values(array_filter(array_map(
            fn ($w) => (int) trim($w),
            explode(',', (string) $this->option('widths'))
        ), fn ($w) => $w > 0));
        sort($widths);

        if (empty($widths)) {
            $this->error('Ningún width válido.');

            return self::FAILURE;
        }

        $quality = max(0, min(100, (int) $this->option('quality')));
        $force = (bool) $this->option('force');
        $limit = (int) $this->option('limit');
        $manager = ImageManager::gd();

        $query = MediaFile::query()
            ->where('type', 'image')
            ->whereIn('mime_type', ['image/jpeg', 'image/jpg', 'image/png']);

        $total = $query->count();
        $this->info("Procesando {$total} imágenes con anchos: ".implode(', ', $widths).' px');

        $count = 0;
        $variantsMade = 0;
        $skipped = 0;
        $failed = 0;
        $bar = $this->output->createProgressBar($limit > 0 ? min($limit, $total) : $total);
        $bar->start();

        $query->lazy()->each(function (MediaFile $file) use (&$count, &$variantsMade, &$skipped, &$failed, $manager, $widths, $quality, $force, $limit, $bar) {
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

                $contents = $disk->get($relative);
                $image = $manager->read($contents);
                $origWidth = $image->width();

                foreach ($widths as $w) {
                    // No up-scale — si el original es más chico, saltar.
                    if ($w >= $origWidth) {
                        continue;
                    }
                    $variantRelative = self::variantPath($relative, $w);
                    if (! $force && $disk->exists($variantRelative)) {
                        $skipped++;

                        continue;
                    }
                    $variantImg = (clone $image)->scale(width: $w);
                    $webpBytes = (string) $variantImg->toWebp($quality);
                    $disk->put($variantRelative, $webpBytes);
                    $variantsMade++;
                }
            } catch (\Throwable $e) {
                $failed++;
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("✔ Variantes generadas: {$variantsMade}");
        $this->line("  Omitidas (ya existían): {$skipped}");
        if ($failed) {
            $this->warn("  Fallidas: {$failed}");
        }

        return self::SUCCESS;
    }

    public static function variantPath(string $relative, int $width): string
    {
        return preg_replace(
            '/\.(jpe?g|png)$/i',
            '-'.$width.'w.webp',
            $relative
        ) ?? $relative;
    }
}
