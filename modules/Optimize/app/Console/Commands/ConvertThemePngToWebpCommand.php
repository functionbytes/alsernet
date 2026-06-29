<?php

namespace Modules\Optimize\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Modules\Media\Models\MediaFile;
use Modules\Media\Models\MediaFolder;
use Modules\Page\Models\Page;
use Modules\Template\Models\Shortcode;
use Symfony\Component\Finder\Finder;

/**
 * Convierte todas las imágenes PNG del tema activo a WebP,
 * las registra en media_files y actualiza las referencias en BD y blades.
 */
class ConvertThemePngToWebpCommand extends Command
{
    protected $signature = 'optimize:convert-theme-png-to-webp
                            {--theme= : Tema objetivo (por defecto el activo)}
                            {--quality=82 : Calidad WebP (0-100)}
                            {--dry-run : Solo muestra qué haría, sin modificar}';

    protected $description = 'Convierte PNG del tema a WebP, registra en media_files y actualiza referencias';

    public function handle(): int
    {
        $theme = $this->option('theme') ?: setting('template', 'caixilhariablanco');
        $quality = (int) $this->option('quality');
        $dryRun = $this->option('dry-run');

        $imagesDir = base_path("platform/themes/{$theme}/public/images");
        if (! is_dir($imagesDir)) {
            $this->error("Directorio no encontrado: {$imagesDir}");

            return self::FAILURE;
        }

        $this->info("Tema: {$theme}");
        $this->info("Directorio: {$imagesDir}");
        $this->newLine();

        // 1. Encontrar todos los PNG
        $finder = (new Finder)->files()->in($imagesDir)->name('*.png');
        $pngFiles = [];
        foreach ($finder as $file) {
            $pngFiles[] = $file->getRealPath();
        }

        $this->info('PNG encontrados: '.count($pngFiles));

        if (count($pngFiles) === 0) {
            $this->warn('No hay imágenes PNG para convertir.');

            return self::SUCCESS;
        }

        // 2. Convertir a WebP
        $converted = 0;
        $skipped = 0;
        $errors = [];
        $webpMap = []; // [pngRelativePath => webpRelativePath]

        $manager = ImageManager::gd();

        foreach ($pngFiles as $pngPath) {
            $relativePath = str_replace($imagesDir.'/', '', $pngPath);
            $webpPath = preg_replace('/\.png$/i', '.webp', $pngPath);
            $webpRelative = preg_replace('/\.png$/i', '.webp', $relativePath);

            $webpMap[$relativePath] = $webpRelative;

            if (is_file($webpPath) && filemtime($webpPath) >= filemtime($pngPath)) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("[DRY-RUN] Convertiría: {$relativePath} -> {$webpRelative}");
                $converted++;

                continue;
            }

            try {
                $image = $manager->read($pngPath);
                $image->toWebp($quality)->save($webpPath);
                $converted++;
                $this->line("✔ {$relativePath} -> {$webpRelative}");
            } catch (\Throwable $e) {
                $errors[] = "{$relativePath}: {$e->getMessage()}";
                $this->error("✘ {$relativePath}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Convertidos: {$converted}, Saltados: {$skipped}, Errores: ".count($errors));

        if ($dryRun) {
            $this->warn('Modo dry-run: no se realizaron cambios.');

            return self::SUCCESS;
        }

        // 3. Registrar WebP en media_files
        $this->newLine();
        $this->info('Registrando WebP en media_files...');

        // Crear carpeta en media si no existe
        $folder = MediaFolder::firstOrCreate(
            ['slug' => 'theme-webp-'.$theme],
            [
                'name' => 'WebP del tema '.$theme,
                'disk' => 'media',
                'user_id' => 1,
            ]
        );

        // Precargar MediaFile existentes para evitar N+1
        $webpNames = array_map('basename', array_values($webpMap));
        $existingNames = MediaFile::query()
            ->where('folder_id', $folder->id)
            ->whereIn('name', $webpNames)
            ->pluck('name')
            ->flip();

        $registered = 0;
        foreach ($webpMap as $relativePath => $webpRelative) {
            $webpPath = $imagesDir.'/'.$webpRelative;
            if (! is_file($webpPath)) {
                continue;
            }

            $webpName = basename($webpRelative);
            if ($existingNames->has($webpName)) {
                continue;
            }

            // Copiar a storage/app/media para que el disco media lo sirva
            $storagePath = "theme-{$theme}/".$webpRelative;
            $fullStorage = storage_path('app/media/'.$storagePath);
            $dir = dirname($fullStorage);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            copy($webpPath, $fullStorage);

            $size = filesize($fullStorage);
            $hash = hash_file('sha256', $fullStorage);

            // Obtener dimensiones
            $dimensions = @getimagesize($fullStorage);
            $metadata = [
                'width' => $dimensions[0] ?? null,
                'height' => $dimensions[1] ?? null,
                'source_png' => 'images/'.$relativePath,
            ];

            MediaFile::create([
                'uid' => (string) Str::ulid(),
                'name' => $webpName,
                'mime_type' => 'image/webp',
                'type' => 'image',
                'size' => $size,
                'url' => $storagePath,
                'alt' => pathinfo($webpName, PATHINFO_FILENAME),
                'folder_id' => $folder->id,
                'user_id' => 1,
                'disk' => 'media',
                'file_hash' => $hash,
                'metadata' => json_encode($metadata),
                'visibility' => 'public',
                'workflow_status' => 'approved',
            ]);

            $registered++;
        }

        $this->info("Registrados en media_files: {$registered}");

        // 4. Actualizar referencias en shortcodes
        $this->newLine();
        $this->info('Actualizando referencias en shortcodes...');
        $shortcodesUpdated = 0;

        $shortcodes = Shortcode::query()
            ->where('render_template', 'like', '%.png%')
            ->orWhere('shortcode_template', 'like', '%.png%')
            ->cursor();

        foreach ($shortcodes as $shortcode) {
            $updated = false;
            $render = $shortcode->render_template;
            $shortTpl = $shortcode->shortcode_template;

            foreach ($webpMap as $relativePath => $webpRelative) {
                $pngUrl = '/pages/images/'.$relativePath;
                $webpUrl = '/pages/images/'.$webpRelative;

                if (str_contains($render, $pngUrl)) {
                    $render = str_replace($pngUrl, $webpUrl, $render);
                    $updated = true;
                }
                if (str_contains($shortTpl, $pngUrl)) {
                    $shortTpl = str_replace($pngUrl, $webpUrl, $shortTpl);
                    $updated = true;
                }
            }

            if ($updated) {
                $shortcode->update([
                    'render_template' => $render,
                    'shortcode_template' => $shortTpl,
                    'updated_at' => now(),
                ]);
                $shortcodesUpdated++;
                $this->line("✔ Shortcode #{$shortcode->id} ({$shortcode->key}) actualizado");
            }
        }

        $this->info("Shortcodes actualizados: {$shortcodesUpdated}");

        // 5. Actualizar referencias en pages
        $this->newLine();
        $this->info('Actualizando referencias en pages...');
        $pagesUpdated = 0;

        $pages = Page::query()
            ->where('content', 'like', '%.png%')
            ->cursor();

        foreach ($pages as $page) {
            $updated = false;
            $content = $page->content;

            foreach ($webpMap as $relativePath => $webpRelative) {
                $pngUrl = '/pages/images/'.$relativePath;
                $webpUrl = '/pages/images/'.$webpRelative;

                if (str_contains($content, $pngUrl)) {
                    $content = str_replace($pngUrl, $webpUrl, $content);
                    $updated = true;
                }
            }

            if ($updated) {
                $page->update([
                    'content' => $content,
                    'updated_at' => now(),
                ]);
                $pagesUpdated++;
                $this->line("✔ Page #{$page->id} ({$page->slug}) actualizado");
            }
        }

        $this->info("Pages actualizados: {$pagesUpdated}");

        // 6. Actualizar archivos Blade
        $this->newLine();
        $this->info('Actualizando referencias en blades del tema...');
        $bladesUpdated = 0;

        $bladeDir = base_path("platform/themes/{$theme}");
        $bladeFinder = (new Finder)->files()->in($bladeDir)->name('*.blade.php');

        foreach ($bladeFinder as $bladeFile) {
            $content = file_get_contents($bladeFile->getRealPath());
            $original = $content;

            foreach ($webpMap as $relativePath => $webpRelative) {
                // Reemplazar referencias como 'images/.../file.png' o "/images/.../file.png"
                $patterns = [
                    "'images/{$relativePath}'",
                    '"images/'.$relativePath.'"',
                    "/images/{$relativePath}",
                ];
                $replacements = [
                    "'images/{$webpRelative}'",
                    '"images/'.$webpRelative.'"',
                    "/images/{$webpRelative}",
                ];

                $content = str_replace($patterns, $replacements, $content);
            }

            if ($content !== $original) {
                file_put_contents($bladeFile->getRealPath(), $content);
                $bladesUpdated++;
                $this->line('✔ Blade actualizado: '.$bladeFile->getRelativePathname());
            }
        }

        $this->info("Blades actualizados: {$bladesUpdated}");

        $this->newLine();
        $this->info('=== Resumen ===');
        $this->table(
            ['Acción', 'Cantidad'],
            [
                ['PNG convertidos a WebP', $converted],
                ['WebP registrados en media_files', $registered],
                ['Shortcodes actualizados', $shortcodesUpdated],
                ['Pages actualizados', $pagesUpdated],
                ['Blades actualizados', $bladesUpdated],
            ]
        );

        return self::SUCCESS;
    }
}
