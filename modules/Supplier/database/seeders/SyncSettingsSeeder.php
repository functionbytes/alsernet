<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\Sync\SyncSetting;

class SyncSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'key' => 'default_batch_size',
                'value' => '50',
                'type' => 'int',
                'label' => 'Tamaño de lote por defecto',
                'description' => 'Cantidad de registros a procesar por lote en sincronizaciones.',
            ],
            [
                'key' => 'erp_timeout',
                'value' => '60',
                'type' => 'int',
                'label' => 'Timeout ERP (segundos)',
                'description' => 'Tiempo máximo de espera para respuestas del ERP.',
            ],
            [
                'key' => 'max_retries',
                'value' => '3',
                'type' => 'int',
                'label' => 'Máximo de reintentos',
                'description' => 'Número máximo de reintentos automáticos para sincronizaciones fallidas.',
            ],
            [
                'key' => 'erp_base_url',
                'value' => env('ERP_URL', ''),
                'type' => 'string',
                'label' => 'URL base del ERP',
                'description' => 'Endpoint base para las APIs de sincronización con Oracle ERP.',
            ],
            [
                'key' => 'sync_enabled',
                'value' => '1',
                'type' => 'bool',
                'label' => 'Sincronización activa',
                'description' => 'Habilita o deshabilita globalmente las sincronizaciones automáticas.',
            ],
            [
                'key' => 'log_level',
                'value' => 'info',
                'type' => 'string',
                'label' => 'Nivel de log',
                'description' => 'Nivel de detalle para los logs de sincronización (debug, info, warning, error).',
            ],
        ];

        foreach ($defaults as $setting) {
            SyncSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
