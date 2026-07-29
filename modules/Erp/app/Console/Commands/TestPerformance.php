<?php

namespace Modules\Erp\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Erp\Models\Oracle\Cliente\ClienteCent;

class TestPerformance extends Command
{
    protected $signature = 'erp:test-performance';

    protected $description = 'Test API endpoint performance to identify bottlenecks';

    public function handle()
    {
        $start = microtime(true);
        $this->info("=== TEST DIRECTO PERFORMANCE ===\n");

        try {
            // 1. Conexión Oracle
            $t1 = microtime(true);
            DB::connection('oracle')->selectOne('SELECT 1 FROM DUAL');
            $t2 = microtime(true);
            $this->line("✓ Conexión Oracle: " . round(($t2 - $t1) * 1000) . "ms");

            // 2. COUNT total
            $t1 = microtime(true);
            $total = DB::connection('oracle')->table('cliente_cent')->count();
            $t2 = microtime(true);
            $this->line("✓ COUNT total: " . round(($t2 - $t1) * 1000) . "ms (Total: $total)");

            // 3. SELECT 10 - SQL directo
            $t1 = microtime(true);
            $rows = DB::connection('oracle')->table('cliente_cent')->limit(10)->get();
            $t2 = microtime(true);
            $this->line("✓ SELECT 10 rows (DB::table): " . round(($t2 - $t1) * 1000) . "ms");

            // 4. Eloquent limit 10
            $t1 = microtime(true);
            $clientes = ClienteCent::limit(10)->get();
            $t2 = microtime(true);
            $this->line("✓ Eloquent::limit(10): " . round(($t2 - $t1) * 1000) . "ms");

            // 5. SELECT solo columnas necesarias
            $t1 = microtime(true);
            $rows2 = DB::connection('oracle')
                ->table('cliente_cent')
                ->select('idcliente', 'nombre', 'apellidos', 'email', 'cif')
                ->limit(10)
                ->get();
            $t2 = microtime(true);
            $this->line("✓ SELECT 5 cols: " . round(($t2 - $t1) * 1000) . "ms");

            // 6. Formateo de 10 registros
            $t1 = microtime(true);
            $formatted = $clientes->map(fn($c) => [
                'idcliente' => $c->idcliente,
                'nombre' => $c->nombre,
                'email' => $c->email,
            ])->toArray();
            $t2 = microtime(true);
            $this->line("✓ Formateo 10 registros: " . round(($t2 - $t1) * 1000) . "ms");

            // 7. JSON encode
            $t1 = microtime(true);
            json_encode(['data' => $formatted]);
            $t2 = microtime(true);
            $this->line("✓ JSON encode: " . round(($t2 - $t1) * 1000) . "ms");

            // 8. Test HTTP request simulado
            $t1 = microtime(true);
            $result = $this->simulateHttpRequest();
            $t2 = microtime(true);
            $this->line("✓ HTTP simulado (full process): " . round(($t2 - $t1) * 1000) . "ms");

            $this->info("\n=== TOTAL: " . round((microtime(true) - $start) * 1000) . "ms ===");

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

    private function simulateHttpRequest()
    {
        $query = ClienteCent::query();
        $limit = 10;
        $offset = 0;

        $total = $query->count();
        $clientes = $query->offset($offset)
            ->limit($limit)
            ->get();

        return $clientes->map(function ($cliente) {
            return [
                'idcliente' => $cliente->idcliente,
                'nombre' => $cliente->nombre,
                'apellidos' => $cliente->apellidos,
                'cif' => $cliente->cif,
                'email' => $cliente->email,
                'codigo_internet' => $cliente->codigo_internet,
                'idtarjeta' => $cliente->idtarjeta,
                'idcategoria_cliente' => $cliente->idcategoria_cliente,
                'ididioma' => $cliente->ididioma,
                'fcreacion' => $cliente->fcreacion?->format('Y-m-d'),
                'fbaja' => $cliente->fbaja?->format('Y-m-d'),
                'faceptacion_lopd' => $cliente->faceptacion_lopd?->format('Y-m-d'),
                'no_informacion_comercial_lopd' => $cliente->no_informacion_comercial_lopd,
                'no_datos_a_terceros_lopd' => $cliente->no_datos_a_terceros_lopd,
                'tiene_interes_legitimo_lopd' => $cliente->tiene_interes_legitimo_lopd,
            ];
        })->toArray();
    }
}
