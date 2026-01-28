# Crear Agentes Personalizados

Este documento explica cómo extender o personalizar los agentes Mailing para necesidades específicas.

## Estructura Base

Todos los agentes heredan de `BaseMailingAgent`, que proporciona las operaciones CRUD básicas.

```php
<?php

namespace App\Console\Commands\Mailing;

class MiTablaAgent extends BaseMailingAgent
{
    // Define la tabla
    protected string $table = 'mailing_mi_tabla';

    // Define la firma del comando
    protected $signature = 'mailing:mi-tabla {action} {--opciones}';

    // Descripción
    protected $description = 'Describe what this agent does';

    // Columnas a ignorar
    protected array $skipColumns = ['id', 'uid', 'created_at', 'updated_at'];

    // Claves foráneas
    protected array $foreignKeys = ['tabla_id'];
}
```

## Personalización Avanzada

### 1. Sobrescribir el Método `list()`

Para agregar filtros o formateo personalizado:

```php
<?php

namespace App\Console\Commands\Mailing;

class SubscribersAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_subscribers';
    protected $signature = 'mailing:subscribers {action} {--status= : Filter by status}';

    /**
     * Sobrescribir listado con filtros
     */
    protected function list(): int
    {
        try {
            $query = DB::connection($this->connection)->table($this->table);

            // Filtrar por status si se proporciona
            if ($this->option('status')) {
                $query->where('status', $this->option('status'));
            }

            $records = $query->get();

            if ($records->isEmpty()) {
                $this->info("No records found in {$this->table}");
                return 0;
            }

            $headers = array_keys((array)$records->first());
            $data = $records->map(fn($record) => (array)$record)->toArray();

            $this->table($headers, $data);
            $this->info("Total: {$records->count()} records");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
```

### 2. Validación Personalizada

Personalizar validación en `create()`:

```php
<?php

namespace App\Console\Commands\Mailing;

class MailListsAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_mail_lists';
    protected $signature = 'mailing:mail-lists {action} {--name=} {--from_email=}';

    protected function create(): int
    {
        try {
            $columns = $this->getTableColumns();
            $data = [];

            // Validar email
            $fromEmail = $this->option('from_email') ?? $this->ask('Enter from_email');
            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                $this->error('Invalid email format');
                return 1;
            }
            $data['from_email'] = $fromEmail;

            // Validar nombre
            $name = $this->option('name') ?? $this->ask('Enter name');
            if (strlen($name) < 3) {
                $this->error('Name must be at least 3 characters');
                return 1;
            }
            $data['name'] = $name;

            // Resto de campos...
            $data['customer_id'] = $this->option('customer_id') ?? $this->ask('Enter customer_id');

            DB::connection($this->connection)->table($this->table)->insert($data);
            $this->info("Mail list created successfully");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
```

### 3. Búsqueda Avanzada

Agregar búsqueda a un agente:

```php
<?php

namespace App\Console\Commands\Mailing;

class ContactsAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_contacts';
    protected $signature = 'mailing:contacts {action} {--search= : Search by name or email}';

    protected function list(): int
    {
        try {
            $query = DB::connection($this->connection)->table($this->table);

            // Búsqueda
            if ($this->option('search')) {
                $search = $this->option('search');
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            $records = $query->get();

            if ($records->isEmpty()) {
                $this->info("No records found");
                return 0;
            }

            $headers = array_keys((array)$records->first());
            $data = $records->map(fn($record) => (array)$record)->toArray();

            $this->table($headers, $data);

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
```

### 4. Acciones Personalizadas

Agregar acciones además de CRUD:

```php
<?php

namespace App\Console\Commands\Mailing;

class SubscribersAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_subscribers';
    protected $signature = 'mailing:subscribers {action : list|create|show|update|delete|export|import}';

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->list(),
            'create' => $this->create(),
            'show' => $this->show(),
            'update' => $this->update(),
            'delete' => $this->delete(),
            'export' => $this->export(),
            'import' => $this->import(),
            default => $this->error("Action '{$action}' not found.") ?? 1,
        };
    }

    /**
     * Exportar a CSV
     */
    private function export(): int
    {
        try {
            $records = DB::connection($this->connection)
                ->table($this->table)
                ->get();

            $filename = 'subscribers_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $path = storage_path("app/{$filename}");

            $file = fopen($path, 'w');

            // Headers
            $headers = array_keys((array)$records->first());
            fputcsv($file, $headers);

            // Datos
            foreach ($records as $record) {
                fputcsv($file, (array)$record);
            }

            fclose($file);

            $this->info("Exported {$records->count()} records to {$filename}");
            $this->line("Path: {$path}");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error exporting: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Importar desde CSV
     */
    private function import(): int
    {
        try {
            $filePath = $this->ask('Enter CSV file path');

            if (!file_exists($filePath)) {
                $this->error("File not found: {$filePath}");
                return 1;
            }

            $file = fopen($filePath, 'r');
            $headers = fgetcsv($file);
            $count = 0;

            while (($row = fgetcsv($file)) !== false) {
                $data = array_combine($headers, $row);
                DB::connection($this->connection)->table($this->table)->insert($data);
                $count++;

                if ($count % 100 == 0) {
                    $this->line("Imported {$count} records...");
                }
            }

            fclose($file);

            $this->info("Successfully imported {$count} records");
            return 0;
        } catch (\Exception $e) {
            $this->error('Error importing: ' . $e->getMessage());
            return 1;
        }
    }
}
```

### 5. Reportes

Agregar reportes a un agente:

```php
<?php

namespace App\Console\Commands\Mailing;

class CampaignsAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_campaigns';
    protected $signature = 'mailing:campaigns {action : list|create|show|update|delete|report}';

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->list(),
            'create' => $this->create(),
            'show' => $this->show(),
            'update' => $this->update(),
            'delete' => $this->delete(),
            'report' => $this->report(),
            default => $this->error("Action '{$action}' not found.") ?? 1,
        };
    }

    /**
     * Reporte de campañas por status
     */
    private function report(): int
    {
        try {
            $campaigns = DB::connection($this->connection)
                ->table($this->table)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get();

            $this->info("\n=== Campaign Report ===\n");

            $headers = ['Status', 'Count'];
            $data = $campaigns->map(fn($c) => [
                $c->status ?? 'NULL',
                $c->count
            ])->toArray();

            $this->table($headers, $data);

            $total = $campaigns->sum('count');
            $this->info("Total campaigns: {$total}\n");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
```

### 6. Bulk Operations

Operaciones en masa:

```php
<?php

namespace App\Console\Commands\Mailing;

class SubscribersAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_subscribers';
    protected $signature = 'mailing:subscribers {action} {--status=} {--bulk-action=}';

    public function handle(): int
    {
        $action = $this->argument('action');

        if ($action === 'bulk') {
            return $this->bulk();
        }

        return parent::handle();
    }

    /**
     * Operación bulk
     */
    private function bulk(): int
    {
        try {
            $status = $this->option('status');
            $bulkAction = $this->option('bulk-action');

            if (!$status || !$bulkAction) {
                $this->error('Require --status and --bulk-action');
                return 1;
            }

            $count = DB::connection($this->connection)
                ->table($this->table)
                ->where('status', $status)
                ->count();

            if (!$this->confirm("Update {$count} records with status '{$status}'?")) {
                return 0;
            }

            $updated = match ($bulkAction) {
                'unsubscribe' => DB::connection($this->connection)
                    ->table($this->table)
                    ->where('status', $status)
                    ->update(['status' => 'unsubscribed']),
                'delete' => DB::connection($this->connection)
                    ->table($this->table)
                    ->where('status', $status)
                    ->delete(),
                default => 0,
            };

            $this->info("Updated {$updated} records");
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
```

## Patrones Recomendados

### 1. Validación de Email

```php
protected function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
```

### 2. Spinner para Operaciones Largas

```php
$this->info('Processing...');
$bar = $this->output->createProgressBar($total);

foreach ($items as $item) {
    // Process
    $bar->advance();
}

$bar->finish();
$this->newLine();
```

### 3. Tabla Formateada

```php
$this->table(
    ['Column 1', 'Column 2', 'Status'],
    [
        ['Value 1', 'Value 2', 'Active'],
        ['Value 3', 'Value 4', 'Inactive'],
    ]
);
```

### 4. Manejo de Transacciones

```php
DB::connection($this->connection)->transaction(function () {
    // Operaciones
    DB::connection($this->connection)->table($this->table)->insert($data);
});
```

## Testing de Agentes Personalizados

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscribersAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_subscribers()
    {
        $this->artisan('mailing:subscribers list')
            ->assertSuccessful();
    }

    public function test_can_create_subscriber()
    {
        $this->artisan('mailing:subscribers create --mail_list_id=1 --email=test@example.com')
            ->assertSuccessful();

        $this->assertDatabaseHas('mailing_subscribers', [
            'email' => 'test@example.com'
        ]);
    }
}
```

## Tips y Mejores Prácticas

1. **Siempre heredar de BaseMailingAgent** - Proporciona base sólida
2. **Usar $this->connection** - Fácil cambiar la conexión
3. **Capturar excepciones** - try/catch en cada método
4. **Validar entrada** - Especialmente emails y números
5. **Documentar acciones** - $description debe ser claro
6. **Proporcionar feedback** - Use $this->info() y $this->error()
7. **Confirmar operaciones destructivas** - $this->confirm()
8. **Usar tablas para mostrar datos** - $this->table()

## Ejemplo Completo: Agente Personalizado

```php
<?php

namespace App\Console\Commands\Mailing;

use Illuminate\Support\Facades\DB;

class CustomersAgent extends BaseMailingAgent
{
    protected string $table = 'mailing_customers';

    protected $signature = 'mailing:customers {action : list|create|show|update|delete|activity} {--search= : Search term} {--status= : Filter by status}';

    protected $description = 'Manage customers with advanced features';

    protected array $skipColumns = ['id', 'created_at', 'updated_at'];

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->listWithFilters(),
            'activity' => $this->showActivity(),
            default => parent::handle(),
        };
    }

    private function listWithFilters(): int
    {
        try {
            $query = DB::connection($this->connection)->table($this->table);

            if ($search = $this->option('search')) {
                $query->where('company', 'LIKE', "%{$search}%");
            }

            if ($status = $this->option('status')) {
                $query->where('status', $status);
            }

            $records = $query->get();

            if ($records->isEmpty()) {
                $this->info('No customers found');
                return 0;
            }

            $headers = array_keys((array)$records->first());
            $this->table($headers, $records->map(fn($r) => (array)$r)->toArray());

            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    private function showActivity(): int
    {
        try {
            $id = $this->option('id');
            if (!$id) {
                $id = $this->ask('Customer ID');
            }

            // Mostrar actividad (requeriría tabla de activity logs)
            $this->info("Activity for customer {$id}");
            // ...

            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
```

## Conclusión

Los agentes Mailing pueden ser extendidos para casi cualquier caso de uso. La clave es:

1. Entender la estructura base de `BaseMailingAgent`
2. Sobrescribir métodos cuando sea necesario
3. Agregar validación personalizada
4. Proporcionar feedback claro al usuario
5. Manejar errores robusto
