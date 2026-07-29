<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\Source\SourceTemplate;

class SupplierSourceTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Shopify API Template',
                'description' => 'Template para conectar con tiendas Shopify mediante su API REST',
                'source_type' => 'api',
                'connection_template' => [
                    'base_url' => '{{shop_url}}/helpdesks/api/{{api_version}}',
                    'auth_type' => 'custom',
                    'auth_header' => 'X-Shopify-Access-Token',
                    'api_key' => '{{access_token}}',
                    'endpoints' => [
                        'inventaries' => '/inventaries.json',
                        'details' => '/inventaries/{{product_id}}.json',
                        'variants' => '/inventaries/{{product_id}}/variants.json',
                    ],
                    'rate_limit' => 40,
                ],
                'extraction_template' => [
                    'product_name' => '{{product.title}}',
                    'description' => '{{product.body_html}}',
                    'price' => '{{product.variants[0].price}}',
                    'sku' => '{{product.variants[0].sku}}',
                    'images' => '{{product.images}}',
                    'tags' => '{{product.tags}}',
                ],
                'schedule_template' => [
                    'frequency' => 'daily',
                    'time' => '02:00',
                    'timezone' => 'Europe/Madrid',
                ],
                'retry_template' => [
                    'max_attempts' => 3,
                    'backoff_strategy' => 'exponential',
                    'initial_delay' => 5,
                ],
                'validation_template' => [
                    'required_fields' => ['title', 'variants'],
                    'price_range' => ['min' => 0, 'max' => 999999],
                ],
                'required_variables' => ['shop_url', 'api_version', 'access_token'],
                'category' => 'E-commerce',
                'tags' => ['shopify', 'api', 'e-commerce'],
                'usage_count' => 0,
                'is_public' => true,
                'created_by' => null,
            ],
            [
                'name' => 'WooCommerce API Template',
                'description' => 'Template para integración con WooCommerce mediante API REST',
                'source_type' => 'api',
                'connection_template' => [
                    'base_url' => '{{site_url}}/wp-json/wc/v3',
                    'auth_type' => 'basic',
                    'username' => '{{consumer_key}}',
                    'password' => '{{consumer_secret}}',
                    'endpoints' => [
                        'inventaries' => '/inventaries',
                        'details' => '/inventaries/{{id}}',
                        'categories' => '/inventaries/categories',
                    ],
                    'rate_limit' => 50,
                ],
                'extraction_template' => [
                    'product_name' => '{{name}}',
                    'description' => '{{description}}',
                    'short_description' => '{{short_description}}',
                    'price' => '{{price}}',
                    'sku' => '{{sku}}',
                    'images' => '{{images}}',
                    'categories' => '{{categories}}',
                    'attributes' => '{{attributes}}',
                ],
                'schedule_template' => [
                    'frequency' => 'hourly',
                    'timezone' => 'Europe/Madrid',
                ],
                'retry_template' => [
                    'max_attempts' => 3,
                    'backoff_strategy' => 'linear',
                    'initial_delay' => 3,
                ],
                'validation_template' => [
                    'required_fields' => ['name', 'price', 'sku'],
                    'price_validation' => true,
                ],
                'required_variables' => ['site_url', 'consumer_key', 'consumer_secret'],
                'category' => 'E-commerce',
                'tags' => ['woocommerce', 'wordpress', 'api'],
                'usage_count' => 0,
                'is_public' => true,
                'created_by' => null,
            ],
            [
                'name' => 'Generic FTP CSV Template',
                'description' => 'Template genérico para importar catálogos CSV desde servidor FTP',
                'source_type' => 'ftp',
                'connection_template' => [
                    'host' => '{{ftp_host}}',
                    'port' => '{{ftp_port}}',
                    'protocol' => '{{protocol}}',
                    'username' => '{{ftp_username}}',
                    'password' => '{{ftp_password}}',
                    'remote_path' => '{{remote_path}}',
                    'file_pattern' => '{{file_pattern}}',
                    'file_format' => 'csv',
                ],
                'extraction_template' => [
                    'delimiter' => '{{delimiter}}',
                    'encoding' => '{{encoding}}',
                    'skip_header' => true,
                    'column_mapping' => [
                        'sku' => 0,
                        'name' => 1,
                        'description' => 2,
                        'price' => 3,
                        'stock' => 4,
                    ],
                ],
                'schedule_template' => [
                    'frequency' => '{{sync_frequency}}',
                    'time' => '03:00',
                    'timezone' => 'Europe/Madrid',
                ],
                'retry_template' => [
                    'max_attempts' => 5,
                    'backoff_strategy' => 'exponential',
                    'initial_delay' => 10,
                ],
                'validation_template' => [
                    'check_file_exists' => true,
                    'min_file_size' => 1024,
                    'required_columns' => ['sku', 'name', 'price'],
                ],
                'required_variables' => ['ftp_host', 'ftp_username', 'ftp_password', 'remote_path', 'file_pattern', 'delimiter', 'encoding', 'sync_frequency'],
                'category' => 'File Import',
                'tags' => ['ftp', 'csv', 'import'],
                'usage_count' => 0,
                'is_public' => true,
                'created_by' => null,
            ],
            [
                'name' => 'E-commerce Web Scraping Template',
                'description' => 'Template para scraping de sitios e-commerce con estructura común',
                'source_type' => 'website',
                'connection_template' => [
                    'base_url' => '{{base_url}}',
                    'product_url_pattern' => '{{product_url_pattern}}',
                    'search_url' => '{{search_url}}',
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'Accept-Language' => 'es-ES,es;q=0.9',
                    ],
                    'rate_limit' => 30,
                ],
                'extraction_template' => [
                    'selectors' => [
                        'name' => '{{selector_name}}',
                        'description' => '{{selector_description}}',
                        'price' => '{{selector_price}}',
                        'images' => '{{selector_images}}',
                        'specifications' => '{{selector_specs}}',
                    ],
                    'wait_for_selector' => '{{selector_name}}',
                    'timeout_ms' => 10000,
                ],
                'schedule_template' => [
                    'frequency' => 'daily',
                    'time' => '04:00',
                    'timezone' => 'Europe/Madrid',
                ],
                'retry_template' => [
                    'max_attempts' => 3,
                    'backoff_strategy' => 'exponential',
                    'initial_delay' => 5,
                ],
                'validation_template' => [
                    'required_selectors' => ['name', 'description'],
                    'validate_url' => true,
                ],
                'required_variables' => ['base_url', 'product_url_pattern', 'selector_name', 'selector_description', 'selector_price'],
                'category' => 'Web Scraping',
                'tags' => ['scraping', 'web', 'e-commerce'],
                'usage_count' => 0,
                'is_public' => true,
                'created_by' => null,
            ],
            [
                'name' => 'Manual Excel Upload Template',
                'description' => 'Template para procesar archivos Excel subidos manualmente',
                'source_type' => 'file',
                'connection_template' => [
                    'local_path' => '{{upload_path}}',
                    'file_pattern' => '*.xlsx',
                    'file_format' => 'xlsx',
                    'encoding' => 'UTF-8',
                ],
                'extraction_template' => [
                    'sheet_name' => '{{sheet_name}}',
                    'start_row' => 2,
                    'column_mapping' => [
                        'A' => 'sku',
                        'B' => 'name',
                        'C' => 'description',
                        'D' => 'price',
                        'E' => 'category',
                        'F' => 'brand',
                    ],
                ],
                'schedule_template' => [
                    'frequency' => 'manual',
                    'trigger' => 'on_upload',
                ],
                'retry_template' => [
                    'max_attempts' => 2,
                    'backoff_strategy' => 'linear',
                    'initial_delay' => 3,
                ],
                'validation_template' => [
                    'required_columns' => ['A', 'B', 'D'],
                    'validate_data_types' => true,
                    'max_rows' => 10000,
                ],
                'required_variables' => ['upload_path', 'sheet_name'],
                'category' => 'File Import',
                'tags' => ['excel', 'manual', 'upload'],
                'usage_count' => 0,
                'is_public' => true,
                'created_by' => null,
            ],
        ];

        foreach ($templates as $templateData) {
            SourceTemplate::updateOrCreate(
                [
                    'name' => $templateData['name'],
                ],
                $templateData
            );
        }

        $this->command->info('Created 5 supplier source templates');
    }
}
