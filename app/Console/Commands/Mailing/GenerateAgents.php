<?php

namespace App\Console\Commands\Mailing;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateAgents extends Command
{
    protected $signature = 'mailing:generate-agents {--tables= : Comma-separated table names to generate agents for}';

    protected $description = 'Generate Mailing Agent classes for all mailing_ tables or specified tables';

    public function handle(): int
    {
        try {
            $tables = $this->getMailingTables();

            if (empty($tables)) {
                $this->error('No mailing tables found');

                return 1;
            }

            $this->info('Found '.count($tables).' mailing tables');

            $generatedCount = 0;
            foreach ($tables as $table) {
                if ($this->generateAgent($table)) {
                    $generatedCount++;
                    $this->line("<fg=green>✓</> Generated agent for {$table}");
                }
            }

            $this->info("\nSuccessfully generated {$generatedCount} agents!");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Get list of mailing tables
     */
    private function getMailingTables(): array
    {
        $allTables = DB::connection('acelle')->getSchemaBuilder()->getTables();
        $mailingTables = [];

        foreach ($allTables as $table) {
            if (str_starts_with($table['name'], 'mailing_')) {
                $mailingTables[] = $table['name'];
            }
        }

        return array_filter($mailingTables, function ($table) {
            return ! $this->agentExists($table);
        });
    }

    /**
     * Check if agent already exists
     */
    private function agentExists(string $table): bool
    {
        $className = $this->tableToClassName($table);
        $filePath = app_path("Console/Commands/Mailing/{$className}.php");

        return file_exists($filePath);
    }

    /**
     * Convert table name to class name
     */
    private function tableToClassName(string $table): string
    {
        // Remove mailing_ prefix
        $name = Str::after($table, 'mailing_');

        // Convert snake_case to StudlyCase
        return Str::studly($name).'Agent';
    }

    /**
     * Generate agent class
     */
    private function generateAgent(string $table): bool
    {
        try {
            $className = $this->tableToClassName($table);
            $commandName = Str::kebab(Str::beforeLast($className, 'Agent'));

            // Get table columns to build signature
            $columns = DB::connection('acelle')
                ->getSchemaBuilder()
                ->getColumns($table);

            $skipColumns = ['id', 'uid', 'created_at', 'updated_at'];
            $foreignKeys = [];
            $optionsList = [];

            foreach ($columns as $column) {
                $name = $column['name'];

                if (in_array($name, $skipColumns)) {
                    continue;
                }

                if (str_ends_with($name, '_id')) {
                    $foreignKeys[] = $name;
                    $optionsList[] = "{--{$name}= : {$name}}";
                } else {
                    $optionsList[] = "{--{$name}= : {$name}}";
                }
            }

            $optionsSignature = implode(' ', $optionsList);
            $foreignKeysArray = "[\n        ".implode(",\n        ", array_map(fn ($k) => "'{$k}'", $foreignKeys))."\n    ]";

            $filePath = app_path("Console/Commands/Mailing/{$className}.php");

            $namespace = 'App\\Console\\Commands\\Mailing';
            $content = <<<PHP
<?php

namespace {$namespace};

class {$className} extends BaseMailingAgent
{
    protected string \$table = '{$table}';

    protected \$signature = 'mailing:{$commandName} {action : Action to perform (list, create, show, update, delete)} {$optionsSignature}';

    protected \$description = 'Manage {$table} table - Create, read, update, and delete records';

    protected array \$skipColumns = ['id', 'uid', 'created_at', 'updated_at'];

    protected array \$foreignKeys = {$foreignKeysArray};
}
PHP;

            file_put_contents($filePath, $content);

            return true;
        } catch (\Exception $e) {
            $this->error("Error generating agent for {$table}: ".$e->getMessage());

            return false;
        }
    }
}
