<?php

namespace App\Console\Commands\Mailing;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

abstract class BaseMailingAgent extends Command
{
    /**
     * The table name (should be overridden in child classes)
     */
    protected string $table = '';

    /**
     * The connection name
     */
    protected string $connection = 'acelle';

    /**
     * List of columns that should be skipped in interactive input
     */
    protected array $skipColumns = ['id', 'uid', 'created_at', 'updated_at'];

    /**
     * Foreign key columns that need validation before creation
     */
    protected array $foreignKeys = [];

    /**
     * Handle the command
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->list(),
            'create' => $this->create(),
            'show' => $this->show(),
            'update' => $this->update(),
            'delete' => $this->delete(),
            default => $this->error("Action '{$action}' not found.") ?? 1,
        };
    }

    /**
     * List all records
     */
    protected function list(): int
    {
        try {
            $records = DB::connection($this->connection)
                ->table($this->table)
                ->get();

            if ($records->isEmpty()) {
                $this->info("No records found in {$this->table}");

                return 0;
            }

            $headers = array_keys((array) $records->first());
            $data = $records->map(fn ($record) => (array) $record)->toArray();

            $this->table($headers, $data);

            return 0;
        } catch (\Exception $e) {
            $this->error('Error listing records: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Create a new record
     */
    protected function create(): int
    {
        try {
            $columns = $this->getTableColumns();
            $data = [];

            foreach ($columns as $column => $info) {
                if (in_array($column, $this->skipColumns)) {
                    continue;
                }

                $type = $info['type'];
                $nullable = $info['nullable'] ?? false;
                $default = $info['default'] ?? null;

                if (in_array($column, $this->foreignKeys)) {
                    $value = $this->option($column);
                    if (! $value) {
                        $value = $this->ask("Enter {$column} (foreignId)");
                    }
                    if (! empty($value) || ! $nullable) {
                        $data[$column] = $value;
                    }

                    continue;
                }

                if ($this->hasOption($column)) {
                    $value = $this->option($column);
                    if (! empty($value) || ! $nullable) {
                        $data[$column] = $value;
                    }
                } else {
                    $required = ! $nullable ? ' (required)' : ' (optional)';
                    $value = $this->ask("Enter {$column} (type: {$type}){$required}", '');

                    if (! empty($value)) {
                        $data[$column] = $value;
                    } elseif ($default !== null) {
                        $data[$column] = $default;
                    } elseif (! $nullable) {
                        $this->error("Field {$column} is required and cannot be empty");

                        return 1;
                    }
                }
            }

            DB::connection($this->connection)->table($this->table)->insert($data);
            $this->info("Record created successfully in {$this->table}");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error creating record: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Show a single record
     */
    protected function show(): int
    {
        try {
            $id = $this->option('id');

            if (! $id) {
                $this->error('--id option is required for show action');

                return 1;
            }

            $record = DB::connection($this->connection)
                ->table($this->table)
                ->where('id', $id)
                ->first();

            if (! $record) {
                $this->error("Record with ID {$id} not found");

                return 1;
            }

            $data = (array) $record;
            $this->info('Record details:');
            foreach ($data as $key => $value) {
                $this->line("<fg=cyan>{$key}</>: ".($value ?? 'NULL'));
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error showing record: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Update a record
     */
    protected function update(): int
    {
        try {
            $id = $this->option('id');

            if (! $id) {
                $this->error('--id option is required for update action');

                return 1;
            }

            $record = DB::connection($this->connection)
                ->table($this->table)
                ->where('id', $id)
                ->first();

            if (! $record) {
                $this->error("Record with ID {$id} not found");

                return 1;
            }

            $columns = $this->getTableColumns();
            $data = [];

            foreach ($columns as $column => $type) {
                if (in_array($column, array_merge($this->skipColumns, ['id']))) {
                    continue;
                }

                if ($this->hasOption($column)) {
                    $data[$column] = $this->option($column);
                }
            }

            if (empty($data)) {
                $this->info('No updates provided');

                return 0;
            }

            DB::connection($this->connection)
                ->table($this->table)
                ->where('id', $id)
                ->update($data);

            $this->info("Record {$id} updated successfully");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error updating record: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Delete a record
     */
    protected function delete(): int
    {
        try {
            $id = $this->option('id');

            if (! $id) {
                $this->error('--id option is required for delete action');

                return 1;
            }

            $record = DB::connection($this->connection)
                ->table($this->table)
                ->where('id', $id)
                ->first();

            if (! $record) {
                $this->error("Record with ID {$id} not found");

                return 1;
            }

            if (! $this->confirm("Are you sure you want to delete record {$id}?")) {
                $this->info('Deletion cancelled');

                return 0;
            }

            DB::connection($this->connection)
                ->table($this->table)
                ->where('id', $id)
                ->delete();

            $this->info("Record {$id} deleted successfully");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error deleting record: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Get table columns and their information
     */
    protected function getTableColumns(): array
    {
        try {
            $columns = DB::connection($this->connection)
                ->getSchemaBuilder()
                ->getColumns($this->table);

            $result = [];
            foreach ($columns as $column) {
                $result[$column['name']] = [
                    'type' => $column['type'],
                    'nullable' => $column['nullable'] ?? false,
                    'default' => $column['default'] ?? null,
                ];
            }

            return $result;
        } catch (\Exception $e) {
            $this->error('Error getting table columns: '.$e->getMessage());

            return [];
        }
    }
}
