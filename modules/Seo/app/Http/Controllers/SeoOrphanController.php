<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Blog\Models\BlogPost;
use Modules\Page\Models\Page;
use Modules\Seo\Models\SeoMeta;
use Modules\Seo\Services\SeoGeneratorService;

class SeoOrphanController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.metas.index');
    }

    public function index(): View
    {
        $orphans = $this->findOrphans();
        $search = request('search', '');

        if ($search !== '') {
            $term = mb_strtolower($search);
            $orphans = collect($orphans)
                ->map(fn ($items) => array_values(array_filter(
                    $items,
                    fn ($item) => str_contains(mb_strtolower($item['title']), $term)
                        || str_contains(mb_strtolower($item['slug']), $term)
                )))
                ->filter(fn ($items) => count($items) > 0)
                ->all();
        }

        return view('Seo::settings.orphans.index', compact('orphans', 'search'));
    }

    /**
     * Auto-generate SEO for a single model.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'model_class' => 'required|string',
            'model_id' => 'required|integer',
        ]);

        $modelClass = $request->input('model_class');
        $modelId = $request->integer('model_id');

        if (! in_array($modelClass, $this->allowedModelClasses(), true)) {
            return response()->json(['status' => false, 'message' => 'Clase de modelo no permitida'], 422);
        }

        $model = $modelClass::find($modelId);

        if (! $model) {
            return response()->json(['status' => false, 'message' => 'Modelo no encontrado'], 404);
        }

        $seoMeta = app(SeoGeneratorService::class)->generateFromModel($model);

        return response()->json([
            'status' => true,
            'message' => 'SEO generado correctamente',
            'seo_meta_id' => $seoMeta->id,
        ]);
    }

    /**
     * Bulk generate SEO for all orphan models.
     */
    public function bulkGenerate(Request $request): JsonResponse
    {
        $request->validate([
            'model_class' => 'required|string',
            'overwrite' => 'boolean',
        ]);

        $modelClass = $request->input('model_class');
        $overwrite = $request->boolean('overwrite', false);

        if (! in_array($modelClass, $this->allowedModelClasses(), true)) {
            return response()->json(['status' => false, 'message' => 'Clase de modelo no permitida'], 422);
        }

        $existingIds = SeoMeta::where('seoable_type', $modelClass)->pluck('seoable_id');
        $modelIds = $modelClass::whereNotIn('id', $existingIds)->pluck('id')->toArray();

        if (empty($modelIds)) {
            return response()->json(['status' => true, 'message' => 'No hay modelos sin SEO', 'generated' => 0, 'skipped' => 0, 'errors' => 0]);
        }

        $result = app(SeoGeneratorService::class)
            ->bulkGenerate($modelClass, $modelIds, $overwrite);

        return response()->json([
            'status' => true,
            'message' => "Generados: {$result['generated']}, Omitidos: {$result['skipped']}, Errores: {$result['errors']}",
            ...$result,
        ]);
    }

    /**
     * @return array<string, array<int, array{id: int, title: string, slug: string, type: string, create_url: string, modelClass: string}>>
     */
    private function findOrphans(): array
    {
        $orphans = [];

        if (class_exists(Page::class)) {
            $existingIds = SeoMeta::where('seoable_type', Page::class)
                ->pluck('seoable_id');

            $pages = Page::query()
                ->whereNotIn('id', $existingIds)
                ->select(['id', 'title', 'slug'])
                ->get();

            if ($pages->isNotEmpty()) {
                $orphans['Páginas'] = $pages->map(fn ($p) => [
                    'id' => $p->id,
                    'title' => $p->title ?? "Página #{$p->id}",
                    'slug' => $p->slug ?? '',
                    'type' => 'Page',
                    'create_url' => route('setting.seo.metas.index'),
                    'modelClass' => Page::class,
                ])->toArray();
            }
        }

        if (class_exists(BlogPost::class)) {
            $existingIds = SeoMeta::where('seoable_type', BlogPost::class)
                ->pluck('seoable_id');

            $posts = BlogPost::query()
                ->whereNotIn('id', $existingIds)
                ->select(['id', 'title', 'slug'])
                ->get();

            if ($posts->isNotEmpty()) {
                $orphans['Blog Posts'] = $posts->map(fn ($p) => [
                    'id' => $p->id,
                    'title' => $p->title ?? "Post #{$p->id}",
                    'slug' => $p->slug ?? '',
                    'type' => 'BlogPost',
                    'create_url' => route('setting.seo.metas.index'),
                    'modelClass' => BlogPost::class,
                ])->toArray();
            }
        }

        return $orphans;
    }

    /**
     * @return array<int, class-string>
     */
    private function allowedModelClasses(): array
    {
        return array_values(array_filter([
            class_exists(Page::class) ? Page::class : null,
            class_exists(BlogPost::class) ? BlogPost::class : null,
        ]));
    }
}
