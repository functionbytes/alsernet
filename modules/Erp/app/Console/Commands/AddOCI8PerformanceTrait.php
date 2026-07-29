<?php

namespace Modules\Erp\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AddOCI8PerformanceTrait extends Command
{
    protected $signature = 'erp:add-oci8-trait';
    protected $description = 'Add UsesOCI8Performance trait to all Oracle models';

    private int $updated = 0;
    private int $skipped = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $this->info('Adding UsesOCI8Performance trait to Oracle models...');
        $this->newLine();

        $modelsPath = module_path('Erp', 'app/Models/Oracle');

        // Get all PHP files excluding Mlog and Rupd (logs)
        $files = collect(File::allFiles($modelsPath))
            ->filter(fn($file) => $file->getExtension() === 'php')
            ->filter(fn($file) => !str_contains($file->getPath(), '/Mlog/'))
            ->filter(fn($file) => !str_contains($file->getPath(), '/Rupd/'));

        $this->info("Found {$files->count()} models to process");
        $this->newLine();

        $bar = $this->output->createProgressBar($files->count());
        $bar->start();

        foreach ($files as $file) {
            try {
                $this->processFile($file->getPathname());
            } catch (\Exception $e) {
                $this->errors++;
                $this->error("\nError in {$file->getFilename()}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info("✅ Updated: {$this->updated} models");
        $this->info("⏭️  Skipped: {$this->skipped} models (already had trait)");
        if ($this->errors > 0) {
            $this->error("❌ Errors: {$this->errors} models");
        }

        return self::SUCCESS;
    }

    private function processFile(string $filePath): void
    {
        $content = file_get_contents($filePath);

        // Skip if already has the trait
        if (str_contains($content, 'use UsesOCI8Performance')) {
            $this->skipped++;
            return;
        }

        // Skip if not a Model class
        if (!str_contains($content, 'extends Model')) {
            $this->skipped++;
            return;
        }

        // Add use statement after other use statements
        $useStatement = "use Modules\\Erp\\Traits\\UsesOCI8Performance;";

        // Find the last use statement
        if (preg_match('/^use .+;$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $lastUsePos = strrpos($content, 'use ', $matches[0][1]);
            $lastUseLine = strpos($content, ';', $lastUsePos) + 1;

            // Insert after the last use statement
            $content = substr_replace(
                $content,
                "\n" . $useStatement,
                $lastUseLine,
                0
            );
        } else {
            // No use statements, add after namespace
            $content = preg_replace(
                '/^namespace .+;$/m',
                "$0\n\n" . $useStatement,
                $content
            );
        }

        // Add trait to class body
        // Find "use SoftDeletes;" or the opening of the class body
        if (str_contains($content, 'use SoftDeletes;')) {
            $content = str_replace(
                'use SoftDeletes;',
                "use SoftDeletes;\n    use UsesOCI8Performance;",
                $content
            );
        } elseif (preg_match('/(class \w+ extends Model\s*\{)/s', $content, $matches)) {
            $content = str_replace(
                $matches[1],
                $matches[1] . "\n    use UsesOCI8Performance;\n",
                $content
            );
        } else {
            $this->skipped++;
            return;
        }

        file_put_contents($filePath, $content);
        $this->updated++;
    }
}
