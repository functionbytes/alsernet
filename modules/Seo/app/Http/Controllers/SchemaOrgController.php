<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Seo\Http\Requests\UpdateSchemaOrgRequest;
use Modules\Seo\Models\SeoMeta;

/**
 * Manage per-page Schema.org JSON-LD overrides. Admins can pick a schema type
 * (Article/Product/FAQ/Recipe/Event/HowTo/WebPage) and edit the raw JSON-LD
 * that gets injected into the public page. The SchemaOrgService handles the
 * automatic/default schemas; this controller manages the manual override.
 */
class SchemaOrgController extends Controller
{
    /**
     * Supported schema.org types with their template structure.
     *
     * @var array<string, array<string, mixed>>
     */
    private const TEMPLATES = [
        'Article' => [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => '',
            'description' => '',
            'image' => '',
            'datePublished' => '',
            'dateModified' => '',
            'author' => ['@type' => 'Person', 'name' => ''],
        ],
        'Product' => [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => '',
            'description' => '',
            'image' => '',
            'sku' => '',
            'brand' => ['@type' => 'Brand', 'name' => ''],
            'offers' => [
                '@type' => 'Offer',
                'price' => '0.00',
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock',
            ],
        ],
        'FAQPage' => [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => '¿Pregunta ejemplo?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Respuesta ejemplo.',
                    ],
                ],
            ],
        ],
        'Recipe' => [
            '@context' => 'https://schema.org',
            '@type' => 'Recipe',
            'name' => '',
            'description' => '',
            'image' => '',
            'recipeIngredient' => [],
            'recipeInstructions' => [],
            'prepTime' => 'PT15M',
            'cookTime' => 'PT30M',
            'recipeYield' => '',
        ],
        'Event' => [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => '',
            'startDate' => '',
            'endDate' => '',
            'location' => [
                '@type' => 'Place',
                'name' => '',
                'address' => '',
            ],
            'description' => '',
        ],
        'HowTo' => [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => '',
            'description' => '',
            'totalTime' => 'PT30M',
            'step' => [],
        ],
        'WebPage' => [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => '',
            'description' => '',
            'url' => '',
        ],
    ];

    public function __construct()
    {
        $this->middleware('can:Seo.metas.update');
    }

    public function edit(SeoMeta $meta): View
    {
        $types = array_keys(self::TEMPLATES);
        $templates = self::TEMPLATES;
        $currentType = $meta->schema_type;
        $currentSchema = $meta->schema_custom;

        return view('Seo::settings.schema-org.edit', compact('meta', 'types', 'templates', 'currentType', 'currentSchema'));
    }

    public function update(UpdateSchemaOrgRequest $request, SeoMeta $meta): RedirectResponse
    {
        $schema = $request->input('schema_custom');
        $decoded = $schema ? json_decode($schema, true) : null;

        $meta->update([
            'schema_type' => $request->input('schema_type') ?: null,
            'schema_custom' => $decoded,
        ]);

        return redirect()
            ->route('setting.seo.metas.edit', $meta)
            ->with('success', 'Schema.org actualizado correctamente.');
    }

    public function template(string $type): JsonResponse
    {
        if (! isset(self::TEMPLATES[$type])) {
            return response()->json(['error' => 'Tipo desconocido'], 404);
        }

        return response()->json([
            'type' => $type,
            'template' => self::TEMPLATES[$type],
        ]);
    }

    public function validateJson(UpdateSchemaOrgRequest $request): JsonResponse
    {
        $schema = $request->input('schema_custom');
        $errors = [];

        $decoded = json_decode($schema ?? '', true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'valid' => false,
                'errors' => ['JSON inválido: '.json_last_error_msg()],
            ]);
        }

        if (! is_array($decoded)) {
            return response()->json([
                'valid' => false,
                'errors' => ['El schema debe ser un objeto JSON.'],
            ]);
        }

        if (! isset($decoded['@context'])) {
            $errors[] = 'Falta `@context` (debe ser "https://schema.org").';
        } elseif ($decoded['@context'] !== 'https://schema.org') {
            $errors[] = '`@context` debe ser exactamente "https://schema.org".';
        }

        if (! isset($decoded['@type'])) {
            $errors[] = 'Falta `@type` (ej: "Article", "Product", "FAQPage").';
        }

        return response()->json([
            'valid' => empty($errors),
            'errors' => $errors,
            'preview' => json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
