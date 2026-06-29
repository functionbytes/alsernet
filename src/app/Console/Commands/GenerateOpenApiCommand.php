<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;
use OpenApi\Generator;
use Psr\Log\AbstractLogger;

/**
 * Generate OpenAPI spec without failing on missing @OA\PathItem warnings.
 *
 * Replaces `php artisan l5-swagger:generate` which converts swagger-php
 * warnings into fatal exceptions. swagger-php v6 emits warnings for files
 * that don't contain path items, but those are non-fatal informational
 * messages.
 */
class GenerateOpenApiCommand extends Command
{
    protected $signature = 'openapi:generate {--documentation=default}';

    protected $description = 'Genera el spec OpenAPI ignorando warnings no-fatales';

    public function handle(): int
    {
        $name = $this->option('documentation');
        $config = config("l5-swagger.documentations.{$name}");

        if (! $config) {
            $this->error("No existe la documentation '{$name}'.");

            return self::FAILURE;
        }

        $paths = $config['paths']['annotations'] ?? [];
        $output = $config['paths']['docs'] ?? storage_path('api-docs');
        $jsonFile = $config['paths']['docs_json'] ?? 'api-docs.json';

        if (! is_dir($output)) {
            @mkdir($output, 0775, true);
        }

        $silentLogger = new class extends AbstractLogger
        {
            public function log($level, $message, array $context = []): void {}
        };

        $generator = (new Generator($silentLogger))
            ->setAnalyser(new ReflectionAnalyser([
                new AttributeAnnotationFactory,
                new DocBlockAnnotationFactory,
            ]));

        $openapi = $generator->generate($paths);

        $jsonPath = rtrim($output, '/').'/'.$jsonFile;
        file_put_contents($jsonPath, $openapi->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->info(sprintf(
            'OpenAPI generado: %d paths, %d tags → %s',
            count((array) ($openapi->paths ?? [])),
            count((array) ($openapi->tags ?? [])),
            $jsonPath
        ));

        return self::SUCCESS;
    }
}
