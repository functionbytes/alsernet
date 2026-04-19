<?php

namespace Modules\Template\Console;

use Illuminate\Console\Command;
use Modules\Template\Models\Template;
use ZipArchive;

class ExportTemplateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'template:export {slug : El slug de la plantilla a exportar} {--output= : Ruta de salida del archivo ZIP}';

    /**
     * The console command description.
     */
    protected $description = 'Exporta una plantilla como archivo ZIP con sus archivos y metadatos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $slug = $this->argument('slug');

        $template = Template::query()->where('slug', $slug)->first();

        if (! $template) {
            $this->error("No se encontro ninguna plantilla con el slug: {$slug}");

            return Command::FAILURE;
        }

        $outputPath = $this->resolveOutputPath($slug);
        $themeDir = base_path("platform/themes/{$slug}");

        $zip = new ZipArchive;

        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("No se pudo crear el archivo ZIP en: {$outputPath}");

            return Command::FAILURE;
        }

        $this->addThemeFiles($zip, $themeDir, $slug);
        $this->addMetadata($zip, $template);

        $zip->close();

        $this->info('Plantilla exportada exitosamente:');
        $this->line($outputPath);

        return Command::SUCCESS;
    }

    /**
     * Resolve the output file path.
     */
    private function resolveOutputPath(string $slug): string
    {
        if ($output = $this->option('output')) {
            return $output;
        }

        $exportsDir = storage_path('app/exports');

        if (! is_dir($exportsDir)) {
            mkdir($exportsDir, 0755, true);
        }

        $date = now()->format('Y-m-d');

        return "{$exportsDir}/template-{$slug}-{$date}.zip";
    }

    /**
     * Add all files from the theme directory into the ZIP.
     */
    private function addThemeFiles(ZipArchive $zip, string $themeDir, string $slug): void
    {
        if (! is_dir($themeDir)) {
            $this->warn("Directorio del tema no encontrado: {$themeDir}");

            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themeDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relativePath = $slug.'/'.ltrim(str_replace($themeDir, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $zip->addFile($file->getPathname(), $relativePath);
        }

        $this->line("Archivos del tema agregados desde: {$themeDir}");
    }

    /**
     * Add template.json metadata file to the ZIP.
     */
    private function addMetadata(ZipArchive $zip, Template $template): void
    {
        $metadata = [
            'name' => $template->name,
            'slug' => $template->slug,
            'description' => $template->description,
            'author' => $template->author,
            'version' => $template->version,
            'content' => $template->content,
            'exported_at' => now()->toIso8601String(),
        ];

        $zip->addFromString('template.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
