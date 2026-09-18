<?php

namespace Modules\Supplier\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;
use Modules\Supplier\Models\Category\Subfamily;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Models\Supplier\Supplier;

/**
 * Datos de demostración para /panel/setting/suppliers/content y sus páginas
 * hermanas (Proveedores/Productos/Categorías). NO se llama desde
 * DatabaseSeeder (eso es solo para permisos/roles/settings de arranque) —
 * se ejecuta a mano con:
 *   php artisan db:seed --class="Modules\Supplier\Database\Seeders\SupplierDemoDataSeeder"
 *
 * Idempotente: usa updateOrCreate/firstOrCreate por clave natural, así que
 * puede relanzarse sin duplicar filas.
 */
class SupplierDemoDataSeeder extends Seeder
{
    /** @var array<string, string> nombre de categoría => tipo (shoes|clothing|equipment) */
    private const CATEGORY_TYPES = [
        'Zapatillas Running' => 'shoes',
        'Ropa Running' => 'clothing',
        'Botas de Fútbol' => 'shoes',
        'Camisetas de Fútbol' => 'clothing',
        'Zapatillas de Baloncesto' => 'shoes',
        'Balones de Baloncesto' => 'equipment',
        'Zapatillas de Tenis' => 'shoes',
        'Raquetas de Tenis' => 'equipment',
        'Palas de Pádel' => 'equipment',
        'Zapatillas de Pádel' => 'shoes',
        'Ropa de Fitness' => 'clothing',
        'Accesorios de Fitness' => 'equipment',
    ];

    /** @var array<string, string[]> categoría => nombres de modelo base */
    private const MODEL_NAMES = [
        'Zapatillas Running' => ['Pegasus', 'Pulse', 'Flow', 'Turbo', 'Glide'],
        'Ropa Running' => ['Camiseta Running', 'Pantalón Running', 'Chaqueta Running', 'Malla Running'],
        'Botas de Fútbol' => ['Predator', 'Phantom', 'Copa', 'Future'],
        'Camisetas de Fútbol' => ['Camiseta Local', 'Camiseta Visitante', 'Camiseta Entrenamiento'],
        'Zapatillas de Baloncesto' => ['Court Vision', 'Fastbreak', 'Rise', 'Dominate'],
        'Balones de Baloncesto' => ['Balón Oficial', 'Balón Street', 'Balón Training'],
        'Zapatillas de Tenis' => ['Court Pro', 'Clay Master', 'Grip Advantage'],
        'Raquetas de Tenis' => ['Raqueta Tour', 'Raqueta Power', 'Raqueta Control'],
        'Palas de Pádel' => ['Pala Control', 'Pala Potencia', 'Pala Híbrida'],
        'Zapatillas de Pádel' => ['Pádel Grip', 'Pádel Move', 'Pádel Pro'],
        'Ropa de Fitness' => ['Camiseta Training', 'Malla Fitness', 'Sudadera Gym'],
        'Accesorios de Fitness' => ['Guantes Training', 'Cinturón Lumbar', 'Muñequeras'],
    ];

    /** Estados de AiContent que llevan contenido ya redactado (vs. vacíos/error). */
    private const CONTENT_STATUSES = [
        'pending_validation', 'in_review', 'needs_revision',
        'validated', 'published', 'published_hidden', 'rejected',
    ];

    private const ERROR_STATUSES = [
        'error_insufficient_info', 'error_source_unavailable', 'error_generation_failed',
    ];

    public function run(): void
    {
        $this->call(AiContentStatusSeeder::class);
        $this->call(SupplierSeeder::class);
        $this->call(SupplierPromptSeeder::class);

        $sports = $this->seedSports();
        $categories = $this->seedCategories($sports);
        $subfamilies = $this->seedSubfamilies($categories);
        $suppliers = Supplier::query()->orderBy('priority')->get();
        $products = $this->seedProducts($suppliers, $categories, $subfamilies);
        $this->seedAiContent($products);

        $this->command->info(sprintf(
            'Supplier demo data: %d deportes, %d categorías, %d subfamilias, %d productos, %d contenidos IA.',
            $sports->count(),
            $categories->count(),
            $subfamilies->count(),
            $products->count(),
            AiContent::query()->count(),
        ));
    }

    /** @return Collection<string, Sport> indexado por nombre */
    private function seedSports()
    {
        // Ciclismo y Natación se seedean como deporte suelto, sin categorías
        // ni productos todavía — refleja un catálogo real con huecos de
        // cobertura pendientes de mapear, no todo tiene que estar completo.
        $names = ['Running', 'Fútbol', 'Baloncesto', 'Tenis', 'Pádel', 'Fitness', 'Ciclismo', 'Natación'];

        return collect($names)->mapWithKeys(function (string $name, int $i) {
            $sport = Sport::updateOrCreate(
                ['erp_id' => 800_000 + $i],
                ['name' => $name, 'short_name' => Str::limit($name, 10, ''), 'available' => true, 'last_sync_at' => now()->subDay()]
            );

            return [$name => $sport];
        });
    }

    /** @return Collection<string, Category> indexado por nombre */
    private function seedCategories($sports)
    {
        $sportBySport = [
            'Zapatillas Running' => 'Running', 'Ropa Running' => 'Running',
            'Botas de Fútbol' => 'Fútbol', 'Camisetas de Fútbol' => 'Fútbol',
            'Zapatillas de Baloncesto' => 'Baloncesto', 'Balones de Baloncesto' => 'Baloncesto',
            'Zapatillas de Tenis' => 'Tenis', 'Raquetas de Tenis' => 'Tenis',
            'Palas de Pádel' => 'Pádel', 'Zapatillas de Pádel' => 'Pádel',
            'Ropa de Fitness' => 'Fitness', 'Accesorios de Fitness' => 'Fitness',
        ];

        $i = 0;

        return collect(self::CATEGORY_TYPES)->keys()->mapWithKeys(function (string $name) use ($sportBySport, $sports, &$i) {
            $i++;
            $category = Category::updateOrCreate(
                ['erp_id' => 810_000 + $i],
                [
                    'sport_id' => $sports[$sportBySport[$name]]->id,
                    'name' => $name,
                    'short_name' => Str::limit($name, 15, ''),
                    'available' => true,
                    'erp_created_at' => now()->subMonths(6),
                    'erp_updated_at' => now()->subDays(7),
                    'last_sync_at' => now()->subDay(),
                ]
            );

            return [$name => $category];
        });
    }

    /** @return Collection<string, Subfamily> indexado por "Categoría · Genero" */
    private function seedSubfamilies($categories)
    {
        $i = 0;
        $out = collect();

        foreach ($categories as $name => $category) {
            if ((self::CATEGORY_TYPES[$name] ?? null) !== 'shoes') {
                continue;
            }

            foreach (['Hombre', 'Mujer'] as $gender) {
                $i++;
                $sub = Subfamily::updateOrCreate(
                    ['erp_id' => 820_000 + $i],
                    [
                        'category_id' => $category->id,
                        'name' => "{$name} {$gender}",
                        'short_name' => $gender,
                        'available' => true,
                        'last_sync_at' => now()->subDay(),
                    ]
                );
                $out["{$name} · {$gender}"] = $sub;
            }
        }

        return $out;
    }

    /** @return Collection<int, Product> */
    private function seedProducts($suppliers, $categories, $subfamilies)
    {
        $products = collect();
        $erpId = 830_000;

        foreach ($suppliers as $supplier) {
            foreach ($categories as $categoryName => $category) {
                $erpId++;
                $models = self::MODEL_NAMES[$categoryName];
                $model = $models[$erpId % count($models)];
                $version = ($erpId % 5) + 20; // "20".."24" como número de versión/año corto
                $type = self::CATEGORY_TYPES[$categoryName];

                $subfamily = null;
                if ($type === 'shoes') {
                    $gender = $erpId % 2 === 0 ? 'Hombre' : 'Mujer';
                    $subfamily = $subfamilies["{$categoryName} · {$gender}"] ?? null;
                }

                $product = Product::updateOrCreate(
                    ['erp_id' => $erpId],
                    [
                        'supplier_id' => $supplier->id,
                        'category_id' => $category->id,
                        'subfamily_id' => $subfamily?->id,
                        'sport_id' => $category->sport_id,
                        'code' => strtoupper(Str::slug($supplier->code.'-'.$model, '')).'-'.$version,
                        'name' => "{$supplier->label} {$model} {$version}",
                        'available' => true,
                        'is_default' => false,
                        'web_published' => $erpId % 4 !== 0,
                        'last_sync_at' => now()->subHours($erpId % 48),
                    ]
                );

                $products->push($product);
            }
        }

        return $products;
    }

    private function seedAiContent($products): void
    {
        $prompts = Prompt::query()->get()->keyBy('label');
        $globalPrompt = $prompts->get('Prompt Global Por defecto');
        $validatorId = User::query()->orderBy('id')->value('id');

        // Reparto de estados: cubre las 8 tarjetas de estadística de la
        // página de Contenido (Total/Por validar/Validado/Publicado/No
        // publicado/Rechazado/Sin generar/Sin contenido). Los últimos
        // productos de la lista se dejan sin AiContent en absoluto para
        // alimentar la tarjeta "Sin contenido".
        $statusCycle = [
            'published', 'published', 'validated', 'validated',
            'pending_validation', 'pending_validation', 'in_review',
            'needs_revision', 'published_hidden', 'rejected',
            'pending_generation', 'generating',
            'error_insufficient_info', 'error_source_unavailable', 'error_generation_failed',
        ];

        $withoutContent = 8; // últimos N productos: sin fila de AiContent
        $eligible = $products->slice(0, max(0, $products->count() - $withoutContent));

        foreach ($eligible->values() as $i => $product) {
            $status = $statusCycle[$i % count($statusCycle)];
            $hasContent = in_array($status, self::CONTENT_STATUSES, true);
            $hasError = in_array($status, self::ERROR_STATUSES, true);

            $prompt = $prompts->get('Prompt Nike Zapatillas')
                ?? $globalPrompt;
            if ($product->supplier->code === 'NIKE' && str_contains($product->name, 'Zapatilla')) {
                $prompt = $prompts->get('Prompt Nike Zapatillas') ?? $globalPrompt;
            } elseif ($product->supplier->code === 'ADIDAS') {
                $prompt = $prompts->get('Prompt Específico Adidas') ?? $globalPrompt;
            } elseif ($product->supplier->code === 'NIKE') {
                $prompt = $prompts->get('Prompt Específico Nike') ?? $globalPrompt;
            } else {
                $prompt = $globalPrompt;
            }

            $content = $hasContent ? $this->buildContent($product) : [
                'generated_name' => null, 'short_description' => null, 'long_description' => null,
                'bullet_points' => null, 'seo_title' => null, 'seo_description' => null, 'seo_keywords' => null,
            ];

            AiContent::updateOrCreate(
                ['supplier_product_id' => $product->id],
                array_merge([
                    'supplier_id' => $product->supplier_id,
                    'product_id' => $product->id,
                    'erp_reference' => (string) $product->erp_id,
                    'model_id' => $product->code,
                    'ean' => null,
                    'status' => $status,
                    'prompt_id' => $prompt?->id,
                    'validated_by' => in_array($status, ['validated', 'published', 'published_hidden'], true) ? $validatorId : null,
                    'validated_at' => in_array($status, ['validated', 'published', 'published_hidden'], true) ? now()->subDays($i % 10 + 1) : null,
                    'rejection_reason' => $status === 'rejected' ? 'Descripción genérica, falta información técnica diferencial del proveedor.' : null,
                    'published_at' => in_array($status, ['published', 'published_hidden'], true) ? now()->subDays($i % 5) : null,
                    'error_message' => $hasError ? $this->errorMessageFor($status) : null,
                ], $content)
            );
        }
    }

    /** @return array<string, mixed> */
    private function buildContent(Product $product): array
    {
        $type = self::CATEGORY_TYPES[$product->category->name] ?? 'equipment';
        $supplierName = $product->supplier->label;
        $categoryName = $product->category->name;

        $intro = match ($type) {
            'shoes' => "Las {$product->name} combinan amortiguación, transpirabilidad y un ajuste preciso pensado para {$categoryName}.",
            'clothing' => "La {$product->name} está confeccionada con tejido técnico ligero, ideal para entrenar con {$supplierName}.",
            default => "El {$product->name} de {$supplierName} está diseñado para un rendimiento consistente en {$categoryName}.",
        };

        $bullets = match ($type) {
            'shoes' => ['Amortiguación reactiva en cada pisada', 'Upper transpirable de secado rápido', 'Suela con tracción multisuperficie', 'Ajuste anatómico ergonómico'],
            'clothing' => ['Tejido técnico transpirable', 'Costuras planas anti-rozaduras', 'Secado rápido', 'Corte ergonómico para libertad de movimiento'],
            default => ['Materiales de alta durabilidad', 'Diseño orientado al rendimiento', 'Fácil mantenimiento', 'Homologado para competición'],
        };

        return [
            'generated_name' => $product->name,
            'short_description' => Str::limit($intro, 155),
            'long_description' => "<p>{$intro}</p><p>Fabricado por <strong>{$supplierName}</strong>, este producto de la categoría <strong>{$categoryName}</strong> está pensado tanto para uso deportivo exigente como para el día a día.</p><ul><li>".implode('</li><li>', $bullets).'</li></ul>',
            'bullet_points' => $bullets,
            'seo_title' => Str::limit("{$product->name} | {$supplierName} {$categoryName}", 65),
            'seo_description' => Str::limit("Compra {$product->name} de {$supplierName}. {$intro}", 155),
            'seo_keywords' => strtolower(Str::slug($product->name, ' ')).', '.strtolower(Str::slug($categoryName, ' ')).', '.strtolower(Str::slug($supplierName, ' ')),
        ];
    }

    private function errorMessageFor(string $status): string
    {
        return match ($status) {
            'error_insufficient_info' => 'No hay suficientes atributos ERP para generar una descripción diferenciada.',
            'error_source_unavailable' => 'La ficha de origen del proveedor no estaba accesible durante la generación.',
            default => 'El modelo de IA devolvió una respuesta incompleta tras 3 reintentos.',
        };
    }
}
