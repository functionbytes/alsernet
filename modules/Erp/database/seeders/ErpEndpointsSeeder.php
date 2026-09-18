<?php

namespace Modules\Erp\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Erp\Models\ErpEndpoint;

class ErpEndpointsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseUrl = config('app.url');

        $endpoints = [
            // ========== API - Eloquent (ORM) ==========
            [
                'name' => 'Clientes (Eloquent)',
                'slug' => 'eloquent-clientes',
                'url' => $baseUrl.'/api/erp/eloquent/clientes',
                'method' => 'GET',
                'description' => 'Listar clientes usando Eloquent ORM. Búsqueda por email, dni, apellidos, fechas.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Artículos (Eloquent)',
                'slug' => 'eloquent-articulos',
                'url' => $baseUrl.'/api/erp/eloquent/articulos',
                'method' => 'GET',
                'description' => 'Listar artículos usando Eloquent ORM.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Albaranes (Eloquent)',
                'slug' => 'eloquent-albaranes',
                'url' => $baseUrl.'/api/erp/eloquent/albaranes',
                'method' => 'GET',
                'description' => 'Listar albaranes usando Eloquent ORM.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Bonos (Eloquent)',
                'slug' => 'eloquent-bonos',
                'url' => $baseUrl.'/api/erp/eloquent/bonos',
                'method' => 'GET',
                'description' => 'Listar bonos usando Eloquent ORM.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Stock (Eloquent)',
                'slug' => 'eloquent-stock',
                'url' => $baseUrl.'/api/erp/eloquent/stock',
                'method' => 'GET',
                'description' => 'Consultar stock usando Eloquent ORM.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Vales (Eloquent)',
                'slug' => 'eloquent-vales',
                'url' => $baseUrl.'/api/erp/eloquent/vales',
                'method' => 'GET',
                'description' => 'Listar vales usando Eloquent ORM.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Pedidos (Eloquent)',
                'slug' => 'eloquent-pedidos',
                'url' => $baseUrl.'/api/erp/eloquent/pedidos',
                'method' => 'GET',
                'description' => 'Listar pedidos de clientes usando Eloquent ORM.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Catálogo (Eloquent)',
                'slug' => 'eloquent-catalogo',
                'url' => $baseUrl.'/api/erp/eloquent/catalogo',
                'method' => 'GET',
                'description' => 'Listar catálogos de clientes usando Eloquent ORM.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],

            // ========== API v2 - Direct SQL (Performance) ==========
            [
                'name' => 'Clientes (Direct SQL)',
                'slug' => 'direct-clientes',
                'url' => $baseUrl.'/api/erp/direct/clientes',
                'method' => 'GET',
                'description' => 'Listar clientes con SQL directo. Performance: ~50-100ms (vs ~200-300ms Eloquent).',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Artículos (Direct SQL)',
                'slug' => 'direct-articulos',
                'url' => $baseUrl.'/api/erp/direct/articulos',
                'method' => 'GET',
                'description' => 'Listar artículos con SQL directo para mayor rendimiento.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Albaranes (Direct SQL)',
                'slug' => 'direct-albaranes',
                'url' => $baseUrl.'/api/erp/direct/albaranes',
                'method' => 'GET',
                'description' => 'Listar albaranes con SQL directo para mayor rendimiento.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Bonos (Direct SQL)',
                'slug' => 'direct-bonos',
                'url' => $baseUrl.'/api/erp/direct/bonos',
                'method' => 'GET',
                'description' => 'Listar bonos con SQL directo para mayor rendimiento.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Stock (Direct SQL)',
                'slug' => 'direct-stock',
                'url' => $baseUrl.'/api/erp/direct/stock',
                'method' => 'GET',
                'description' => 'Consultar stock con SQL directo para mayor rendimiento.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Vales (Direct SQL)',
                'slug' => 'direct-vales',
                'url' => $baseUrl.'/api/erp/direct/vales',
                'method' => 'GET',
                'description' => 'Listar vales con SQL directo para mayor rendimiento.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Pedidos (Direct SQL)',
                'slug' => 'direct-pedidos',
                'url' => $baseUrl.'/api/erp/direct/pedidos',
                'method' => 'GET',
                'description' => 'Listar pedidos con SQL directo para mayor rendimiento.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
            [
                'name' => 'Catálogo (Direct SQL)',
                'slug' => 'direct-catalogo',
                'url' => $baseUrl.'/api/erp/direct/catalogo',
                'method' => 'GET',
                'description' => 'Listar catálogos con SQL directo para mayor rendimiento.',
                'timeout' => 30,
                'retry_attempts' => 3,
                'content_type' => 'application/json',
                'is_active' => true,
            ],
        ];

        foreach ($endpoints as $endpointData) {
            // Mark as auto-discovered (from API routes)
            // Set to inactive initially - users must configure before using
            $endpointData['is_auto_discovered'] = true;
            $endpointData['is_active'] = false;

            ErpEndpoint::updateOrCreate(
                ['slug' => $endpointData['slug']],
                $endpointData
            );
        }

        $this->command->info('✅ '.count($endpoints).' endpoints ERP registrados correctamente');
    }
}
