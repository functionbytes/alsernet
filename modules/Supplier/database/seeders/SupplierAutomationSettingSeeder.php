<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\Automation\AutomationSetting;

class SupplierAutomationSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Connection Settings
            [
                'key' => 'automation.max_concurrent_jobs',
                'value' => '5',
                'type' => 'integer',
                'category' => 'connection',
                'description' => 'Número máximo de trabajos de automatización concurrentes',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.default_timeout',
                'value' => '300',
                'type' => 'integer',
                'category' => 'connection',
                'description' => 'Timeout Por defecto para operaciones de scraping/API en segundos',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.retry_attempts',
                'value' => '3',
                'type' => 'integer',
                'category' => 'connection',
                'description' => 'Número de intentos de reintento en caso de fallo',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.retry_backoff_multiplier',
                'value' => '2',
                'type' => 'integer',
                'category' => 'connection',
                'description' => 'Multiplicador para el backoff exponencial en reintentos',
                'is_sensitive' => false,
            ],

            // Security Settings
            [
                'key' => 'automation.enable_ssl_verification',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'security',
                'description' => 'Verificar certificados SSL en conexiones HTTPS',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.allowed_domains',
                'value' => json_encode([
                    'nike.com',
                    'adidas.com',
                    'puma.com',
                    'asics.com',
                    'newbalance.com',
                ]),
                'type' => 'json',
                'category' => 'security',
                'description' => 'Lista de dominios permitidos para scraping',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.encryption_key',
                'value' => 'your-encryption-key-here',
                'type' => 'encrypted',
                'category' => 'security',
                'description' => 'Clave de encriptación para credenciales sensibles',
                'is_sensitive' => true,
            ],

            // Rate Limiting Settings
            [
                'key' => 'automation.global_rate_limit_per_minute',
                'value' => '100',
                'type' => 'integer',
                'category' => 'limits',
                'description' => 'Límite global de requests por minuto para todas las fuentes',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.rate_limit_per_source',
                'value' => '30',
                'type' => 'integer',
                'category' => 'limits',
                'description' => 'Límite de requests por minuto por fuente individual',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.max_products_per_batch',
                'value' => '100',
                'type' => 'integer',
                'category' => 'limits',
                'description' => 'Número máximo de productos a procesar en un lote',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.max_file_size_mb',
                'value' => '50',
                'type' => 'integer',
                'category' => 'limits',
                'description' => 'Tamaño máximo de archivo para importación en MB',
                'is_sensitive' => false,
            ],

            // AI Generation Settings
            [
                'key' => 'automation.ai_provider',
                'value' => 'anthropic',
                'type' => 'string',
                'category' => 'defaults',
                'description' => 'Proveedor de IA para generación de contenido (anthropic, openai)',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.ai_model',
                'value' => 'claude-3-5-sonnet-20241022',
                'type' => 'string',
                'category' => 'defaults',
                'description' => 'Modelo de IA a utilizar',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.ai_temperature',
                'value' => '0.7',
                'type' => 'string',
                'category' => 'defaults',
                'description' => 'Temperatura para generación de contenido (0.0 - 1.0)',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.ai_max_tokens',
                'value' => '4096',
                'type' => 'integer',
                'category' => 'defaults',
                'description' => 'Número máximo de tokens en la respuesta de IA',
                'is_sensitive' => false,
            ],

            // Content Validation Settings
            [
                'key' => 'automation.require_manual_validation',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'defaults',
                'description' => 'Requiere validación manual antes de publicar contenido generado',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.auto_publish_threshold',
                'value' => '0.95',
                'type' => 'string',
                'category' => 'defaults',
                'description' => 'Umbral de confianza para auto-publicación (0.0 - 1.0)',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.min_description_length',
                'value' => '200',
                'type' => 'integer',
                'category' => 'defaults',
                'description' => 'Longitud mínima de descripción en caracteres',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.max_description_length',
                'value' => '2000',
                'type' => 'integer',
                'category' => 'defaults',
                'description' => 'Longitud máxima de descripción en caracteres',
                'is_sensitive' => false,
            ],

            // Scheduling Settings
            [
                'key' => 'automation.default_schedule_time',
                'value' => '02:00',
                'type' => 'string',
                'category' => 'defaults',
                'description' => 'Hora Por defecto para trabajos programados (HH:MM)',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.timezone',
                'value' => 'Europe/Madrid',
                'type' => 'string',
                'category' => 'defaults',
                'description' => 'Zona horaria para trabajos programados',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.enable_auto_scheduling',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'defaults',
                'description' => 'Habilitar programación automática de trabajos',
                'is_sensitive' => false,
            ],

            // Notification Settings
            [
                'key' => 'automation.notify_on_completion',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'defaults',
                'description' => 'Enviar notificación cuando se completa un trabajo',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.notify_on_error',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'defaults',
                'description' => 'Enviar notificación cuando falla un trabajo',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.notification_email',
                'value' => 'helpdesks@alsernet.com',
                'type' => 'string',
                'category' => 'defaults',
                'description' => 'Email para notificaciones del sistema',
                'is_sensitive' => false,
            ],

            // Logging Settings
            [
                'key' => 'automation.log_level',
                'value' => 'info',
                'type' => 'string',
                'category' => 'defaults',
                'description' => 'Nivel de logging (debug, info, warning, error)',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.log_retention_days',
                'value' => '90',
                'type' => 'integer',
                'category' => 'defaults',
                'description' => 'Días de retención de logs',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.enable_detailed_logging',
                'value' => 'false',
                'type' => 'boolean',
                'category' => 'defaults',
                'description' => 'Habilitar logging detallado (incluye payloads completos)',
                'is_sensitive' => false,
            ],

            // Cache Settings
            [
                'key' => 'automation.cache_source_data',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'defaults',
                'description' => 'Cachear datos de fuentes para reutilización',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.cache_ttl_hours',
                'value' => '24',
                'type' => 'integer',
                'category' => 'defaults',
                'description' => 'Tiempo de vida del cache en horas',
                'is_sensitive' => false,
            ],

            // N8N Integration Settings
            [
                'key' => 'automation.n8n_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'category' => 'connection',
                'description' => 'Habilitar integración con N8N para scraping',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.n8n_base_url',
                'value' => 'http://n8n.local:5678',
                'type' => 'string',
                'category' => 'connection',
                'description' => 'URL base del servidor N8N',
                'is_sensitive' => false,
            ],
            [
                'key' => 'automation.n8n_api_key',
                'value' => 'n8n-api-key-placeholder',
                'type' => 'encrypted',
                'category' => 'security',
                'description' => 'API Key para autenticación con N8N',
                'is_sensitive' => true,
            ],
        ];

        foreach ($settings as $settingData) {
            AutomationSetting::updateOrCreate(
                ['key' => $settingData['key']],
                $settingData
            );
        }

        $this->command->info('Created '.count($settings).' automation backups');
    }
}
