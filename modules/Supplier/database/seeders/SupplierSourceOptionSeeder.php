<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Source\SourceOption;

class SupplierSourceOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Nike Website Options
        $nikeWebsite = Source::where('label', 'Nike Official Website (ES)')->first();
        if ($nikeWebsite) {
            $this->createWebsiteOptions($nikeWebsite, [
                'base_url' => 'https://www.nike.com/es',
                'product_url_pattern' => '/es/t/{slug}',
                'search_url' => '/es/w?q={query}',
                'selectors' => json_encode([
                    'name' => 'h1.product-title',
                    'description' => '.description-preview',
                    'full_description' => '.description-text',
                    'specifications' => '.product-specs li',
                    'features' => '.product-features li',
                    'images' => '.product-gallery img@src',
                    'price' => '.product-price',
                ]),
                'pagination' => json_encode([
                    'enabled' => false,
                    'selector' => '.pagination a.next',
                    'max_pages' => 1,
                ]),
                'headers' => json_encode([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept-Language' => 'es-ES,es;q=0.9',
                ]),
                'rate_limit' => '30',
            ]);
        }

        // Nike FTP Options
        $nikeFtp = Source::where('label', 'Nike FTP Catalog')->first();
        if ($nikeFtp) {
            $this->createFtpOptions($nikeFtp, [
                'host' => 'ftp.nike-catalog.com',
                'port' => '22',
                'protocol' => 'sftp',
                'username' => 'nike_alsernet',
                'password' => 'encrypted:nike_ftp_password',
                'remote_path' => '/catalogs/ES/2024/',
                'file_pattern' => 'catalog_*.xlsx',
                'file_format' => 'xlsx',
                'sync_frequency' => 'daily',
            ]);
        }

        // Nike API Options
        $nikeApi = Source::where('label', 'Nike Product API')->first();
        if ($nikeApi) {
            $this->createApiOptions($nikeApi, [
                'base_url' => 'https://api.nike.com/v1',
                'auth_type' => 'bearer',
                'api_key' => 'encrypted:nike_api_key_12345',
                'auth_header' => 'Authorization',
                'endpoints' => json_encode([
                    'inventaries' => '/inventaries',
                    'details' => '/inventaries/{id}',
                    'search' => '/inventaries/search?q={query}',
                ]),
                'rate_limit' => '100',
                'response_format' => 'json',
            ]);
        }

        // Adidas Website Options
        $adidasWebsite = Source::where('label', 'Adidas Official Website (ES)')->first();
        if ($adidasWebsite) {
            $this->createWebsiteOptions($adidasWebsite, [
                'base_url' => 'https://www.adidas.es',
                'product_url_pattern' => '/{slug}.html',
                'search_url' => '/search?q={query}',
                'selectors' => json_encode([
                    'name' => 'h1.gl-heading',
                    'description' => '.gl-product-description',
                    'specifications' => '.product-details li',
                    'technologies' => '.tech-features span',
                    'images' => '.product-images img@data-src',
                ]),
                'headers' => json_encode([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                    'Accept-Language' => 'es-ES,es;q=0.9',
                ]),
                'rate_limit' => '25',
            ]);
        }

        // Adidas File Options
        $adidasFile = Source::where('label', 'Adidas Excel Catalog')->first();
        if ($adidasFile) {
            $this->createFileOptions($adidasFile, [
                'local_path' => '/storage/suppliers/adidas/catalogs/',
                'file_pattern' => '*.xlsx',
                'file_format' => 'xlsx',
                'encoding' => 'UTF-8',
                'sheet_name' => 'Productos',
            ]);
        }

        // Puma FTP Options
        $pumaFtp = Source::where('label', 'Puma FTP CSV Catalog')->first();
        if ($pumaFtp) {
            $this->createFtpOptions($pumaFtp, [
                'host' => 'ftp.puma-catalog.com',
                'port' => '21',
                'protocol' => 'ftp',
                'username' => 'puma_user',
                'password' => 'encrypted:puma_ftp_pass',
                'remote_path' => '/exports/spain/',
                'file_pattern' => 'products_*.csv',
                'file_format' => 'csv',
                'sync_frequency' => 'weekly',
            ]);

            // Add CSV-specific options
            SourceOption::updateOrCreate(
                [
                    'source_id' => $pumaFtp->id,
                    'option_key' => 'delimiter',
                ],
                [
                    'option_value' => ';',
                    'option_type' => SourceOption::TYPE_STRING,
                    'is_required' => true,
                ]
            );

            SourceOption::updateOrCreate(
                [
                    'source_id' => $pumaFtp->id,
                    'option_key' => 'encoding',
                ],
                [
                    'option_value' => 'ISO-8859-1',
                    'option_type' => SourceOption::TYPE_STRING,
                    'is_required' => false,
                ]
            );
        }

        // Asics API Options
        $asicsApi = Source::where('label', 'Asics Product API')->first();
        if ($asicsApi) {
            $this->createApiOptions($asicsApi, [
                'base_url' => 'https://api.asics.com/v2',
                'auth_type' => 'oauth2',
                'api_key' => 'encrypted:asics_client_id',
                'endpoints' => json_encode([
                    'inventaries' => '/inventaries',
                    'details' => '/inventaries/{sku}',
                    'categories' => '/categories',
                ]),
                'rate_limit' => '60',
                'response_format' => 'json',
            ]);

            // Add OAuth2 specific options
            SourceOption::updateOrCreate(
                [
                    'source_id' => $asicsApi->id,
                    'option_key' => 'client_secret',
                ],
                [
                    'option_value' => 'encrypted:asics_client_secret',
                    'option_type' => SourceOption::TYPE_STRING,
                    'is_required' => true,
                ]
            );

            SourceOption::updateOrCreate(
                [
                    'source_id' => $asicsApi->id,
                    'option_key' => 'token_url',
                ],
                [
                    'option_value' => 'https://auth.asics.com/oauth/token',
                    'option_type' => SourceOption::TYPE_URL,
                    'is_required' => true,
                ]
            );
        }

        // New Balance Website Options
        $newBalanceWebsite = Source::where('label', 'New Balance Web Scraping')->first();
        if ($newBalanceWebsite) {
            $this->createWebsiteOptions($newBalanceWebsite, [
                'base_url' => 'https://www.newbalance.es',
                'product_url_pattern' => '/pd/{slug}',
                'search_url' => '/search?text={query}',
                'selectors' => json_encode([
                    'name' => 'h1.product-name',
                    'description' => '.product-description-text',
                    'specs' => '.product-attributes li',
                    'images' => '.product-carousel img@src',
                ]),
                'headers' => json_encode([
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
                ]),
                'rate_limit' => '20',
            ]);
        }

        $this->command->info('Created source options for all supplier sources');
    }

    /**
     * Create website-specific options for a source
     */
    private function createWebsiteOptions(Source $source, array $options): void
    {
        $commonOptions = [
            'base_url' => SourceOption::TYPE_URL,
            'product_url_pattern' => SourceOption::TYPE_STRING,
            'search_url' => SourceOption::TYPE_STRING,
            'selectors' => SourceOption::TYPE_JSON,
            'pagination' => SourceOption::TYPE_JSON,
            'headers' => SourceOption::TYPE_JSON,
            'rate_limit' => SourceOption::TYPE_INTEGER,
        ];

        foreach ($options as $key => $value) {
            $type = $commonOptions[$key] ?? SourceOption::TYPE_STRING;

            SourceOption::updateOrCreate(
                [
                    'source_id' => $source->id,
                    'option_key' => $key,
                ],
                [
                    'option_value' => $value,
                    'option_type' => $type,
                    'is_required' => in_array($key, ['base_url', 'selectors']),
                ]
            );
        }
    }

    /**
     * Create FTP-specific options for a source
     */
    private function createFtpOptions(Source $source, array $options): void
    {
        $commonOptions = [
            'host' => SourceOption::TYPE_STRING,
            'port' => SourceOption::TYPE_INTEGER,
            'protocol' => SourceOption::TYPE_STRING,
            'username' => SourceOption::TYPE_STRING,
            'password' => SourceOption::TYPE_STRING,
            'remote_path' => SourceOption::TYPE_PATH,
            'file_pattern' => SourceOption::TYPE_STRING,
            'file_format' => SourceOption::TYPE_STRING,
            'sync_frequency' => SourceOption::TYPE_STRING,
        ];

        foreach ($options as $key => $value) {
            $type = $commonOptions[$key] ?? SourceOption::TYPE_STRING;

            SourceOption::updateOrCreate(
                [
                    'source_id' => $source->id,
                    'option_key' => $key,
                ],
                [
                    'option_value' => $value,
                    'option_type' => $type,
                    'is_required' => in_array($key, ['host', 'username', 'password', 'remote_path']),
                ]
            );
        }
    }

    /**
     * Create API-specific options for a source
     */
    private function createApiOptions(Source $source, array $options): void
    {
        $commonOptions = [
            'base_url' => SourceOption::TYPE_URL,
            'auth_type' => SourceOption::TYPE_STRING,
            'api_key' => SourceOption::TYPE_STRING,
            'auth_header' => SourceOption::TYPE_STRING,
            'endpoints' => SourceOption::TYPE_JSON,
            'rate_limit' => SourceOption::TYPE_INTEGER,
            'response_format' => SourceOption::TYPE_STRING,
        ];

        foreach ($options as $key => $value) {
            $type = $commonOptions[$key] ?? SourceOption::TYPE_STRING;

            SourceOption::updateOrCreate(
                [
                    'source_id' => $source->id,
                    'option_key' => $key,
                ],
                [
                    'option_value' => $value,
                    'option_type' => $type,
                    'is_required' => in_array($key, ['base_url', 'auth_type', 'api_key']),
                ]
            );
        }
    }

    /**
     * Create file-specific options for a source
     */
    private function createFileOptions(Source $source, array $options): void
    {
        $commonOptions = [
            'local_path' => SourceOption::TYPE_PATH,
            'file_pattern' => SourceOption::TYPE_STRING,
            'file_format' => SourceOption::TYPE_STRING,
            'encoding' => SourceOption::TYPE_STRING,
            'sheet_name' => SourceOption::TYPE_STRING,
            'delimiter' => SourceOption::TYPE_STRING,
        ];

        foreach ($options as $key => $value) {
            $type = $commonOptions[$key] ?? SourceOption::TYPE_STRING;

            SourceOption::updateOrCreate(
                [
                    'source_id' => $source->id,
                    'option_key' => $key,
                ],
                [
                    'option_value' => $value,
                    'option_type' => $type,
                    'is_required' => in_array($key, ['local_path', 'file_format']),
                ]
            );
        }
    }
}
