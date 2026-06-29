<?php

namespace Modules\Page\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReindexPagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page:reindex
                          {--drop : Drop existing FULLTEXT index before recreating}
                          {--repair : Repair table and optimize}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex pages table for full-text search';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting pages reindexing...');

        try {
            // Check if table exists
            if (! Schema::hasTable('pages')) {
                $this->error('Pages table does not exist!');

                return self::FAILURE;
            }

            // Drop existing index if requested
            if ($this->option('drop')) {
                $this->info('Dropping existing FULLTEXT index...');
                try {
                    DB::statement('ALTER TABLE pages DROP INDEX fulltext_search');
                    $this->info('Existing index dropped successfully.');
                } catch (\Exception $e) {
                    $this->warn('No existing index to drop or error occurred: '.$e->getMessage());
                }
            }

            // Create FULLTEXT index
            $this->info('Creating FULLTEXT index on (title, content, description)...');

            try {
                DB::statement('ALTER TABLE pages ADD FULLTEXT fulltext_search(title, content, description)');
                $this->info('FULLTEXT index created successfully!');
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'Duplicate key name')) {
                    $this->warn('FULLTEXT index already exists.');
                } else {
                    $this->error('Error creating FULLTEXT index: '.$e->getMessage());

                    return self::FAILURE;
                }
            }

            // Repair and optimize if requested
            if ($this->option('repair')) {
                $this->info('Repairing and optimizing pages table...');

                try {
                    DB::statement('REPAIR TABLE pages');
                    $this->info('Table repaired.');

                    DB::statement('OPTIMIZE TABLE pages');
                    $this->info('Table optimized.');
                } catch (\Exception $e) {
                    $this->warn('Error during repair/optimize: '.$e->getMessage());
                }
            }

            // Show index information
            $this->info('Current indexes on pages table:');
            $indexes = DB::select('SHOW INDEX FROM pages');

            $this->table(
                ['Key Name', 'Column Name', 'Index Type'],
                collect($indexes)
                    ->filter(function ($index) {
                        return $index->Key_name === 'fulltext_search';
                    })
                    ->map(function ($index) {
                        return [
                            $index->Key_name,
                            $index->Column_name,
                            $index->Index_type,
                        ];
                    })
                    ->toArray()
            );

            // Test the index
            $this->info('Testing FULLTEXT search...');
            $count = DB::table('pages')
                ->whereRaw('MATCH(title, content, description) AGAINST(? IN BOOLEAN MODE)', ['test*'])
                ->count();

            $this->info("FULLTEXT search is working! Found {$count} pages matching 'test'.");

            $this->info('');
            $this->info('✓ Reindexing completed successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error during reindexing: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }
}
