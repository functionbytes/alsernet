<?php

namespace Modules\Seo\Console\Commands;

use Illuminate\Console\Command;
use Modules\Seo\Services\SchemaOrgService;

class GenerateSchemasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:generate-schemas
                            {type? : The schema type to generate (article, organization, breadcrumb, faq, webpage, product, localbusiness)}
                            {--pretty : Output formatted JSON}
                            {--test : Run test generation for all schema types}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and test Schema.org structured data schemas';

    /**
     * Schema service instance.
     */
    protected SchemaOrgService $schemaService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SchemaOrgService $schemaService)
    {
        parent::__construct();
        $this->schemaService = $schemaService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('test')) {
            return $this->runTests();
        }

        $type = $this->argument('type');

        if (! $type) {
            $type = $this->choice(
                'Select schema type to generate',
                ['article', 'organization', 'breadcrumb', 'faq', 'webpage', 'product', 'localbusiness', 'graph'],
                0
            );
        }

        $schema = match (strtolower($type)) {
            'article' => $this->generateArticleExample(),
            'organization' => $this->generateOrganizationExample(),
            'breadcrumb' => $this->generateBreadcrumbExample(),
            'faq' => $this->generateFaqExample(),
            'webpage' => $this->generateWebPageExample(),
            'product' => $this->generateProductExample(),
            'localbusiness' => $this->generateLocalBusinessExample(),
            'graph' => $this->generateGraphExample(),
            default => null,
        };

        if (! $schema) {
            $this->error("Unknown schema type: {$type}");

            return Command::FAILURE;
        }

        $this->info("Generated {$type} schema:");
        $this->line('');

        $json = $this->schemaService->renderJsonLd($schema, $this->option('pretty'));
        $this->line($json);

        $this->line('');
        $this->info('Script tag version:');
        $this->line('');
        $this->line($this->schemaService->renderScriptTag($schema, $this->option('pretty')));

        return Command::SUCCESS;
    }

    /**
     * Run tests for all schema types.
     */
    protected function runTests(): int
    {
        $this->info('Testing Schema.org structured data generation...');
        $this->line('');

        $tests = [
            'Article Schema' => fn () => $this->generateArticleExample(),
            'Organization Schema' => fn () => $this->generateOrganizationExample(),
            'Breadcrumb Schema' => fn () => $this->generateBreadcrumbExample(),
            'FAQ Schema' => fn () => $this->generateFaqExample(),
            'WebPage Schema' => fn () => $this->generateWebPageExample(),
            'Product Schema' => fn () => $this->generateProductExample(),
            'LocalBusiness Schema' => fn () => $this->generateLocalBusinessExample(),
            'Graph Schema' => fn () => $this->generateGraphExample(),
        ];

        $passed = 0;
        $failed = 0;

        foreach ($tests as $name => $testFn) {
            try {
                $schema = $testFn();

                // Validate required fields (Graph schema has @graph instead of @type)
                if (! isset($schema['@context'])) {
                    throw new \Exception('Missing required @context');
                }

                if (! isset($schema['@type']) && ! isset($schema['@graph'])) {
                    throw new \Exception('Missing required @type or @graph');
                }

                // Try to encode as JSON
                $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('JSON encoding error: '.json_last_error_msg());
                }

                $this->info("[PASS] {$name}");
                $passed++;
            } catch (\Exception $e) {
                $this->error("[FAIL] {$name}: ".$e->getMessage());
                $failed++;
            }
        }

        $this->line('');
        $this->info("Tests completed: {$passed} passed, {$failed} failed");

        // Check configuration
        $this->line('');
        $this->info('Configuration check:');
        $this->line('Schema enabled: '.(config('Seo.schema.enabled') ? 'Yes' : 'No'));
        $this->line('Organization name: '.config('Seo.schema.organization.name'));
        $this->line('Organization URL: '.config('Seo.schema.organization.url'));

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Generate example Article schema.
     */
    protected function generateArticleExample(): array
    {
        return $this->schemaService->generateArticleSchema(
            new class
            {
                public $title = 'Example Article Title';

                public $description = 'This is an example article description for testing.';

                public $created_at;

                public $updated_at;

                public $content = 'This is the article content with multiple words for word count testing.';

                public function __construct()
                {
                    $this->created_at = now();
                    $this->updated_at = now();
                }
            },
            [
                'author' => 'John Doe',
            ]
        );
    }

    /**
     * Generate example Organization schema.
     */
    protected function generateOrganizationExample(): array
    {
        return $this->schemaService->generateOrganizationSchema();
    }

    /**
     * Generate example Breadcrumb schema.
     */
    protected function generateBreadcrumbExample(): array
    {
        return $this->schemaService->generateBreadcrumbSchema([
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Blog', 'url' => url('/blog')],
            ['name' => 'Article Title', 'url' => url('/blog/article-slug')],
        ]);
    }

    /**
     * Generate example FAQ schema.
     */
    protected function generateFaqExample(): array
    {
        return $this->schemaService->generateFAQSchema([
            [
                'question' => 'What is Schema.org?',
                'answer' => 'Schema.org is a collaborative project to create, maintain, and promote schemas for structured data on the web.',
            ],
            [
                'question' => 'Why use structured data?',
                'answer' => 'Structured data helps search engines understand your content better and can lead to rich results in search.',
            ],
        ]);
    }

    /**
     * Generate example WebPage schema.
     */
    protected function generateWebPageExample(): array
    {
        return $this->schemaService->generateWebPageSchema([
            'title' => 'Example Page Title',
            'description' => 'This is an example page description.',
            'url' => url('/example-page'),
            'image' => asset('images/example.jpg'),
        ]);
    }

    /**
     * Generate example Product schema.
     */
    protected function generateProductExample(): array
    {
        return $this->schemaService->generateProductSchema(
            new class
            {
                public $name = 'Example Product';

                public $description = 'This is an example product description.';

                public $sku = 'PROD-12345';

                public $price = 99.99;

                public $image = 'https://example.com/product.jpg';
            },
            [
                'brand' => 'Example Brand',
                'currency' => 'USD',
                'rating' => [
                    'value' => 4.5,
                    'count' => 120,
                ],
            ]
        );
    }

    /**
     * Generate example LocalBusiness schema.
     */
    protected function generateLocalBusinessExample(): array
    {
        return $this->schemaService->generateLocalBusinessSchema([
            'type' => 'Restaurant',
            'name' => 'Example Restaurant',
            'phone' => '+1-555-123-4567',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Anytown',
                'region' => 'CA',
                'postal_code' => '12345',
                'country' => 'US',
            ],
            'geo' => [
                'latitude' => '37.7749',
                'longitude' => '-122.4194',
            ],
            'price_range' => '$$',
        ]);
    }

    /**
     * Generate example Graph schema.
     */
    protected function generateGraphExample(): array
    {
        return $this->schemaService->generateGraphSchema([
            $this->generateOrganizationExample(),
            $this->generateWebPageExample(),
            $this->generateBreadcrumbExample(),
        ]);
    }
}
