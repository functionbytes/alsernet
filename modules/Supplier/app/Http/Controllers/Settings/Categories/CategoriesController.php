<?php

namespace Modules\Supplier\Http\Controllers\Settings\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Supplier\Http\Requests\Category\BulkActionCategoryRequest;
use Modules\Supplier\Http\Requests\Category\UpdateCategoryRequest;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;
use Modules\Supplier\Models\Category\Subfamily;
use Modules\Supplier\Models\Product\Product;
use Spatie\SimpleExcel\SimpleExcelReader;

class CategoriesController extends Controller
{
    public function index(Request $request): View
    {
        $pageTitle = 'Categorías del ERP';
        $breadcrumb = 'Configuración / Proveedores / Categorías ERP';

        $type        = $request->get('type', 'familias');
        $searchKey   = $request->get('search');
        $filterSport = $request->get('sport_id');
        $filterCat   = $request->get('erp_categoria_id');
        $filterFam   = $request->get('familia_id');

        switch ($type) {
            case 'sports':
                $query = Sport::query();
                $title = 'Deportes';
                if ($searchKey) $query->where('name', 'like', "%{$searchKey}%");
                break;

            case 'categorias':
                $query = Category::selectRaw('erp_categoria_id as id, erp_categoria_name as name, sport_id, COUNT(*) as familias_count')
                    ->whereNotNull('erp_categoria_id')
                    ->groupBy('erp_categoria_id', 'erp_categoria_name', 'sport_id');
                $title = 'Categorías';
                if ($searchKey)   $query->having('erp_categoria_name', 'like', "%{$searchKey}%");
                if ($filterSport) $query->where('sport_id', $filterSport);
                break;

            case 'subfamilias':
                $query = Subfamily::query()->with('category');
                $title = 'Subfamilias';
                if ($searchKey) $query->where('name', 'like', "%{$searchKey}%");
                if ($filterFam)  $query->where('category_id', $filterFam);
                elseif ($filterCat) {
                    $famIds = Category::where('erp_categoria_id', $filterCat)->pluck('id');
                    $query->whereIn('category_id', $famIds);
                } elseif ($filterSport) {
                    $famIds = Category::where('sport_id', $filterSport)->pluck('id');
                    $query->whereIn('category_id', $famIds);
                }
                break;

            default: // familias
                $type  = 'familias';
                $query = Category::query()->with('sport')->withCount('products');
                $title = 'Familias';
                if ($searchKey)  $query->where('name', 'like', "%{$searchKey}%");
                if ($filterSport) $query->where('sport_id', $filterSport);
                if ($filterCat)   $query->where('erp_categoria_id', $filterCat);
                break;
        }

        $perPage = (int) $request->input('per_page', 50);
        $perPage = in_array($perPage, [10, 20, 50, 100, 200]) ? $perPage : 50;

        $items = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // Para subfamilias: cargar prompts asignados (por subfamily_id o en subfamily_ids JSON)
        $subfamilyPrompts = collect();
        if ($type === 'subfamilias' && $items->isNotEmpty()) {
            $subfamilyIds = $items->pluck('id')->toArray();
            $allPrompts = \Modules\Supplier\Models\Prompt\Prompt::select('id', 'uid', 'label', 'subfamily_id', 'subfamily_ids')
                ->where(function ($q) use ($subfamilyIds) {
                    $q->whereIn('subfamily_id', $subfamilyIds);
                    foreach ($subfamilyIds as $sfId) {
                        // MySQL puede almacenar como integer o string en JSON — probar ambos
                        $q->orWhereJsonContains('subfamily_ids', (int) $sfId);
                        $q->orWhereJsonContains('subfamily_ids', (string) $sfId);
                    }
                })
                ->where('is_active', true)
                ->get();

            // Indexar por subfamilia_id
            foreach ($allPrompts as $prompt) {
                // Por subfamily_id (single)
                if ($prompt->subfamily_id && in_array($prompt->subfamily_id, $subfamilyIds)) {
                    $subfamilyPrompts->push(['sf_id' => $prompt->subfamily_id, 'prompt' => $prompt]);
                }
                // Por subfamily_ids (multi)
                foreach (($prompt->subfamily_ids ?? []) as $sfId) {
                    if (in_array($sfId, $subfamilyIds)) {
                        $subfamilyPrompts->push(['sf_id' => $sfId, 'prompt' => $prompt]);
                    }
                }
            }
        }

        // Datos para filtros en cascada
        $sports = Sport::orderBy('name')->get();

        $categorias = Category::select('erp_categoria_id', 'erp_categoria_name')
            ->whereNotNull('erp_categoria_id')
            ->when($filterSport, fn($q) => $q->where('sport_id', $filterSport))
            ->groupBy('erp_categoria_id', 'erp_categoria_name')
            ->orderBy('erp_categoria_name')
            ->get();

        $familias = Category::select('id', 'name')
            ->when($filterSport, fn($q) => $q->where('sport_id', $filterSport))
            ->when($filterCat,   fn($q) => $q->where('erp_categoria_id', $filterCat))
            ->orderBy('name')
            ->get();

        $stats = [
            'sports'      => Sport::count(),
            'categorias'  => Category::select('erp_categoria_id')->whereNotNull('erp_categoria_id')->distinct()->count(),
            'familias'    => Category::count(),
            'subfamilias' => Subfamily::count(),
            'last_sync'   => Category::max('last_sync_at'),
        ];

        return view('supplier::settings.views.categories.index', compact(
            'items', 'stats', 'type', 'title', 'searchKey',
            'sports', 'categorias', 'familias',
            'filterSport', 'filterCat', 'filterFam',
            'subfamilyPrompts',
            'pageTitle', 'breadcrumb'
        ));
    }

    public function bulkAction(BulkActionCategoryRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $ids    = $request->validated('ids');
        $type   = $request->validated('type') ?? 'categories';
        $count  = 0;

        $model = $type === 'sports' ? Sport::class : Category::class;

        match ($action) {
            'delete'  => $count = $model::whereIn('id', $ids)->delete(),
            'enable'  => $count = $model::whereIn('id', $ids)->update(['available' => true]),
            'disable' => $count = $model::whereIn('id', $ids)->update(['available' => false]),
        };

        $noun = $type === 'sports' ? 'deporte(s)' : 'categoría(s)';
        $labels = [
            'delete'  => 'eliminado(s)',
            'enable'  => 'activado(s)',
            'disable' => 'desactivado(s)',
        ];

        return response()->json([
            'message' => "{$count} {$noun} {$labels[$action]}.",
            'count'   => $count,
        ]);
    }

    public function edit(int $id): View
    {
        $category = Category::findOrFail($id);
        $sports = Sport::orderBy('name')->get();
        $pageTitle = 'Editar categoría';
        $breadcrumb = 'Configuración / Proveedores / Categorías / Editar';

        return view('supplier::settings.views.categories.edit', compact('category', 'sports', 'pageTitle', 'breadcrumb'));
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);

            $validated = $request->validated();
            $category->update([
                'name' => $validated['name'],
                'short_name' => $validated['short_name'] ?: null,
                'sport_id' => $validated['sport_id'] ?: null,
                'available' => (bool) ($validated['available'] ?? true),
            ]);

            return response()->json(['success' => true, 'message' => 'Categoría actualizada correctamente']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        if ($request->get('type') === 'sports') {
            Sport::findOrFail($id)->delete();
            $message = 'Deporte eliminado correctamente';
        } else {
            Category::findOrFail($id)->delete();
            $message = 'Categoría eliminada correctamente';
        }

        return redirect()->route('settings.suppliers.categories.index', ['type' => $request->get('type')])
            ->with('success', $message);
    }

    public function importExcelPage(): View
    {
        $config = [
            'import_level'      => Setting::get('supplier.excel_import.import_level', 'grupo'),
            'strip_prefix'      => Setting::get('supplier.excel_import.strip_prefix', 'T.'),
            'col_deporte_id'    => Setting::get('supplier.excel_import.col_deporte_id', 'IDDEPORTE_CL'),
            'col_deporte_name'  => Setting::get('supplier.excel_import.col_deporte_name', 'DESC_DEPORTE_CL'),
            'col_categoria_id'  => Setting::get('supplier.excel_import.col_categoria_id', 'IDCATEGORIA_CL'),
            'col_categoria_name'=> Setting::get('supplier.excel_import.col_categoria_name', 'DESC_CATEGORIA_CL'),
            'col_familia_id'    => Setting::get('supplier.excel_import.col_familia_id', 'IDFAMILIA_CL'),
            'col_familia_name'  => Setting::get('supplier.excel_import.col_familia_name', 'DESC_FAMILIA_CL'),
            'col_subfamilia_id' => Setting::get('supplier.excel_import.col_subfamilia_id', 'IDSUBFAMILIA_CL'),
            'col_subfamilia_name'=> Setting::get('supplier.excel_import.col_subfamilia_name', 'DESC_SUBFAMILIA_CL'),
            'col_grupo_id'      => Setting::get('supplier.excel_import.col_grupo_id', 'IDGRUPO_CL'),
            'col_grupo_name'    => Setting::get('supplier.excel_import.col_grupo_name', 'DESC_GRUPO_CL'),
        ];

        $stats = [
            'familias'    => Category::count(),
            'subfamilias' => Subfamily::count(),
            'sports'      => Sport::count(),
            'last_import' => Category::whereNotNull('last_sync_at')->max('last_sync_at'),
        ];

        return view('supplier::settings.views.categories.import-excel', compact('config', 'stats'));
    }

    public function excelConfig(): View
    {
        $config = [
            'import_level'      => Setting::get('supplier.excel_import.import_level', 'grupo'),
            'col_deporte_id'    => Setting::get('supplier.excel_import.col_deporte_id', 'IDDEPORTE_CL'),
            'col_deporte_name'  => Setting::get('supplier.excel_import.col_deporte_name', 'DESC_DEPORTE_CL'),
            'col_categoria_id'  => Setting::get('supplier.excel_import.col_categoria_id', 'IDCATEGORIA_CL'),
            'col_categoria_name'=> Setting::get('supplier.excel_import.col_categoria_name', 'DESC_CATEGORIA_CL'),
            'col_familia_id'    => Setting::get('supplier.excel_import.col_familia_id', 'IDFAMILIA_CL'),
            'col_familia_name'  => Setting::get('supplier.excel_import.col_familia_name', 'DESC_FAMILIA_CL'),
            'col_subfamilia_id' => Setting::get('supplier.excel_import.col_subfamilia_id', 'IDSUBFAMILIA_CL'),
            'col_subfamilia_name'=> Setting::get('supplier.excel_import.col_subfamilia_name', 'DESC_SUBFAMILIA_CL'),
            'col_grupo_id'      => Setting::get('supplier.excel_import.col_grupo_id', 'IDGRUPO_CL'),
            'col_grupo_name'    => Setting::get('supplier.excel_import.col_grupo_name', 'DESC_GRUPO_CL'),
            'col_desc_completa' => Setting::get('supplier.excel_import.col_desc_completa', 'DESC_COMPLETA'),
            'strip_prefix'      => Setting::get('supplier.excel_import.strip_prefix', 'T.'),
        ];

        return view('supplier::settings.views.categories.excel-config', compact('config'));
    }

    public function saveExcelConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'import_level'       => ['required', 'in:deporte,categoria,familia,subfamilia,grupo'],
            'col_deporte_id'     => ['required', 'string', 'max:100'],
            'col_deporte_name'   => ['required', 'string', 'max:100'],
            'col_categoria_id'   => ['required', 'string', 'max:100'],
            'col_categoria_name' => ['required', 'string', 'max:100'],
            'col_familia_id'     => ['required', 'string', 'max:100'],
            'col_familia_name'   => ['required', 'string', 'max:100'],
            'col_subfamilia_id'  => ['required', 'string', 'max:100'],
            'col_subfamilia_name'=> ['required', 'string', 'max:100'],
            'col_grupo_id'       => ['required', 'string', 'max:100'],
            'col_grupo_name'     => ['required', 'string', 'max:100'],
            'col_desc_completa'  => ['nullable', 'string', 'max:100'],
            'strip_prefix'       => ['nullable', 'string', 'max:20'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set("supplier.excel_import.{$key}", $value ?? '');
        }

        return response()->json(['success' => true, 'message' => 'Configuración guardada correctamente.']);
    }

    public function importExcelStream(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $request->validate([
                'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            ]);

            $file     = $request->file('file');
            $ext      = strtolower($file->getClientOriginalExtension());
            $tmpPath  = $file->storeAs('tmp', 'cat_import_' . uniqid() . '.' . $ext, 'local');
            $fullPath = storage_path('app/' . $tmpPath);

            if ($ext === 'xls') {
                $xlsxPath    = storage_path('app/tmp/cat_import_' . uniqid() . '.xlsx');
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
                $writer      = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($xlsxPath);
                @unlink($fullPath);
                $fullPath = $xlsxPath;
            }

            $level       = Setting::get('supplier.excel_import.import_level', 'grupo');
            $stripPrefix = Setting::get('supplier.excel_import.strip_prefix', 'T.');
            $n = fn($s) => ($stripPrefix && str_starts_with((string)$s, $stripPrefix))
                ? substr((string)$s, strlen($stripPrefix)) : (string)$s;

            $colIdMap   = [
                'deporte'    => Setting::get('supplier.excel_import.col_deporte_id',    'IDDEPORTE_CL'),
                'categoria'  => Setting::get('supplier.excel_import.col_categoria_id',  'IDCATEGORIA_CL'),
                'familia'    => Setting::get('supplier.excel_import.col_familia_id',    'IDFAMILIA_CL'),
                'subfamilia' => Setting::get('supplier.excel_import.col_subfamilia_id', 'IDSUBFAMILIA_CL'),
                'grupo'      => Setting::get('supplier.excel_import.col_grupo_id',      'IDGRUPO_CL'),
            ];
            $colNameMap = [
                'deporte'    => Setting::get('supplier.excel_import.col_deporte_name',    'DESC_DEPORTE_CL'),
                'categoria'  => Setting::get('supplier.excel_import.col_categoria_name',  'DESC_CATEGORIA_CL'),
                'familia'    => Setting::get('supplier.excel_import.col_familia_name',    'DESC_FAMILIA_CL'),
                'subfamilia' => Setting::get('supplier.excel_import.col_subfamilia_name', 'DESC_SUBFAMILIA_CL'),
                'grupo'      => Setting::get('supplier.excel_import.col_grupo_name',      'DESC_GRUPO_CL'),
            ];

            $colId      = $colIdMap[$level];
            $colName    = $colNameMap[$level];
            $colSportId = Setting::get('supplier.excel_import.col_deporte_id',   'IDDEPORTE_CL');
            $colSportName = Setting::get('supplier.excel_import.col_deporte_name', 'DESC_DEPORTE_CL');

            return response()->stream(function () use ($fullPath, $level, $n, $colId, $colName, $colSportId, $colSportName) {
                if (request()->hasSession()) {
                    session()->save();
                }

                $send = function (string $event, array $data) {
                    echo "event: {$event}\n";
                    echo 'data: ' . json_encode($data) . "\n\n";
                    if (ob_get_level() > 0) @ob_flush();
                    flush();
                };

                try {
                    $rows = SimpleExcelReader::create($fullPath)->getRows()->toArray();
                    $total = count($rows);

                    $send('start', ['total' => $total, 'level' => $level]);

                    $imported    = 0;
                    $skipped     = 0;
                    $errors      = [];
                    $sportCache  = [];
                    $categoryCache = [];

                    $colSubfamiliaId = Setting::get('supplier.excel_import.col_subfamilia_id', 'IDSUBFAMILIA_CL');
                    $colSubfamiliaName = Setting::get('supplier.excel_import.col_subfamilia_name', 'DESC_SUBFAMILIA_CL');
                    $colFamiliaId = Setting::get('supplier.excel_import.col_familia_id', 'IDFAMILIA_CL');
                    $colFamiliaName = Setting::get('supplier.excel_import.col_familia_name', 'DESC_FAMILIA_CL');
                    $colCategoriaId = Setting::get('supplier.excel_import.col_categoria_id', 'IDCATEGORIA_CL');
                    $colCategoriaName = Setting::get('supplier.excel_import.col_categoria_name', 'DESC_CATEGORIA_CL');

                    foreach ($rows as $index => $row) {
                        $erpDeporteId = isset($row[$colSportId]) ? (int) $row[$colSportId] : null;
                        $erpDeporteName = isset($row[$colSportName]) ? $n($row[$colSportName]) : null;
                        $erpCategoriaId = isset($row[$colCategoriaId]) ? (int) $row[$colCategoriaId] : null;
                        $erpCategoriaName = isset($row[$colCategoriaName]) ? $n($row[$colCategoriaName]) : null;
                        $erpFamiliaId = isset($row[$colFamiliaId]) ? (int) $row[$colFamiliaId] : null;
                        $erpFamiliaName = isset($row[$colFamiliaName]) ? $n($row[$colFamiliaName]) : null;
                        $erpSubfamiliaId = isset($row[$colSubfamiliaId]) ? (int) $row[$colSubfamiliaId] : null;
                        $erpSubfamiliaName = isset($row[$colSubfamiliaName]) ? $n($row[$colSubfamiliaName]) : null;

                        // 1. Crear Deporte
                        $sportId = null;
                        if ($erpDeporteId && !isset($sportCache[$erpDeporteId])) {
                            $sport = Sport::updateOrCreate(['erp_id' => $erpDeporteId], ['name' => $erpDeporteName ?? "Deporte {$erpDeporteId}", 'available' => true]);
                            $sportCache[$erpDeporteId] = $sport->id;
                        }
                        if ($erpDeporteId) $sportId = $sportCache[$erpDeporteId] ?? null;

                        // 2. Crear Categoría (familia padre)
                        $categoryId = null;
                        if ($erpFamiliaId) {
                            $cacheKey = "{$erpFamiliaId}";
                            if (!isset($categoryCache[$cacheKey])) {
                                $category = Category::updateOrCreate(
                                    ['erp_id' => $erpFamiliaId],
                                    [
                                        'name' => $erpFamiliaName ?? "Familia {$erpFamiliaId}",
                                        'sport_id' => $sportId,
                                        'erp_sport_id' => $erpDeporteId,
                                        'erp_categoria_id' => $erpCategoriaId,
                                        'erp_categoria_name' => $erpCategoriaName,
                                        'available' => true,
                                        'last_sync_at' => now(),
                                    ]
                                );
                                $categoryCache[$cacheKey] = $category->id;
                            }
                            $categoryId = $categoryCache[$cacheKey];
                        }

                        // 3. Crear Subfamilia si es el nivel
                        if ($level === 'subfamilia' && $erpSubfamiliaId) {
                            if (!$categoryId) {
                                $errors[] = 'Fila ' . ($index + 2) . ': Subfamilia sin familia padre.';
                                $skipped++;
                                continue;
                            }
                            Subfamily::updateOrCreate(
                                ['erp_id' => $erpSubfamiliaId],
                                ['name' => $erpSubfamiliaName ?? "Subfamilia {$erpSubfamiliaId}", 'category_id' => $categoryId, 'available' => true]
                            );
                            $imported++;
                        } elseif ($level !== 'subfamilia' && $erpFamiliaId && $erpFamiliaName) {
                            Category::updateOrCreate(
                                ['erp_id' => $erpFamiliaId],
                                [
                                    'name' => $erpFamiliaName,
                                    'sport_id' => $sportId,
                                    'erp_sport_id' => $erpDeporteId,
                                    'erp_categoria_id' => $erpCategoriaId,
                                    'erp_categoria_name' => $erpCategoriaName,
                                    'available' => true,
                                    'last_sync_at' => now(),
                                ]
                            );
                            $imported++;
                        } else {
                            $skipped++;
                        }

                        if (($index + 1) % 10 === 0 || ($index + 1) === $total) {
                            $send('progress', [
                                'processed' => $index + 1,
                                'total'     => $total,
                                'percent'   => round((($index + 1) / $total) * 100),
                                'imported'  => $imported,
                                'skipped'   => $skipped,
                            ]);
                        }
                    }

                    $send('done', [
                        'imported' => $imported,
                        'skipped'  => $skipped,
                        'errors'   => array_slice($errors, 0, 10),
                        'message'  => "{$imported} registros importados." . ($skipped ? " {$skipped} omitidos." : ''),
                    ]);

                } catch (\Exception $e) {
                    $send('error', ['message' => 'Error: ' . $e->getMessage()]);
                } finally {
                    if (isset($fullPath) && file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                }
            }, 200, [
                'Content-Type'      => 'text/event-stream',
                'Cache-Control'     => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]);
        } catch (\Exception $e) {
            return response()->stream(function () use ($e) {
                $send = function (string $event, array $data) {
                    echo "event: {$event}\n";
                    echo 'data: ' . json_encode($data) . "\n\n";
                    if (ob_get_level() > 0) @ob_flush();
                    flush();
                };
                $send('error', ['message' => 'Error: ' . $e->getMessage()]);
            }, 200, [
                'Content-Type'      => 'text/event-stream',
                'Cache-Control'     => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]);
        }
    }

    public function importExcel(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            ]);

            $file = $request->file('file');
            $ext  = strtolower($file->getClientOriginalExtension());

            $tmpPath  = $file->storeAs('tmp', 'cat_import_' . uniqid() . '.' . $ext, 'local');
            $fullPath = storage_path('app/' . $tmpPath);

            // Convertir .xls a .xlsx para que SimpleExcelReader lo pueda leer
            if ($ext === 'xls') {
                $xlsxPath = storage_path('app/tmp/cat_import_' . uniqid() . '.xlsx');
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($xlsxPath);
                @unlink($fullPath);
                $fullPath = $xlsxPath;
            }

            // Leer configuración de columnas
            $level        = Setting::get('supplier.excel_import.import_level', 'grupo');
            $stripPrefix  = Setting::get('supplier.excel_import.strip_prefix', 'T.');

            $colIdMap = [
                'deporte'    => Setting::get('supplier.excel_import.col_deporte_id',    'IDDEPORTE_CL'),
                'categoria'  => Setting::get('supplier.excel_import.col_categoria_id',  'IDCATEGORIA_CL'),
                'familia'    => Setting::get('supplier.excel_import.col_familia_id',    'IDFAMILIA_CL'),
                'subfamilia' => Setting::get('supplier.excel_import.col_subfamilia_id', 'IDSUBFAMILIA_CL'),
                'grupo'      => Setting::get('supplier.excel_import.col_grupo_id',      'IDGRUPO_CL'),
            ];
            $colNameMap = [
                'deporte'    => Setting::get('supplier.excel_import.col_deporte_name',    'DESC_DEPORTE_CL'),
                'categoria'  => Setting::get('supplier.excel_import.col_categoria_name',  'DESC_CATEGORIA_CL'),
                'familia'    => Setting::get('supplier.excel_import.col_familia_name',    'DESC_FAMILIA_CL'),
                'subfamilia' => Setting::get('supplier.excel_import.col_subfamilia_name', 'DESC_SUBFAMILIA_CL'),
                'grupo'      => Setting::get('supplier.excel_import.col_grupo_name',      'DESC_GRUPO_CL'),
            ];

            $colId      = $colIdMap[$level];
            $colName    = $colNameMap[$level];
            $colSportId = Setting::get('supplier.excel_import.col_deporte_id',   'IDDEPORTE_CL');
            $colSportName = Setting::get('supplier.excel_import.col_deporte_name', 'DESC_DEPORTE_CL');

            $rows = SimpleExcelReader::create($fullPath)->getRows();

            $imported = 0;
            $skipped  = 0;
            $errors   = [];
            $sportCache = [];
            $categoryCache = [];
            $familyCache = [];

            // Configuración de columnas
            $colSubfamiliaId = Setting::get('supplier.excel_import.col_subfamilia_id', 'IDSUBFAMILIA_CL');
            $colSubfamiliaName = Setting::get('supplier.excel_import.col_subfamilia_name', 'DESC_SUBFAMILIA_CL');
            $colFamiliaId = Setting::get('supplier.excel_import.col_familia_id', 'IDFAMILIA_CL');
            $colFamiliaName = Setting::get('supplier.excel_import.col_familia_name', 'DESC_FAMILIA_CL');
            $colCategoriaId = Setting::get('supplier.excel_import.col_categoria_id', 'IDCATEGORIA_CL');
            $colCategoriaName = Setting::get('supplier.excel_import.col_categoria_name', 'DESC_CATEGORIA_CL');

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;

                $erpDeporteId = isset($row[$colSportId]) ? trim((string) $row[$colSportId]) : null;
                $erpDeporteName = isset($row[$colSportName]) ? trim((string) $row[$colSportName]) : null;

                $erpCategoriaId = isset($row[$colCategoriaId]) ? trim((string) $row[$colCategoriaId]) : null;
                $erpCategoriaName = isset($row[$colCategoriaName]) ? trim((string) $row[$colCategoriaName]) : null;

                $erpFamiliaId = isset($row[$colFamiliaId]) ? trim((string) $row[$colFamiliaId]) : null;
                $erpFamiliaName = isset($row[$colFamiliaName]) ? trim((string) $row[$colFamiliaName]) : null;

                $erpSubfamiliaId = isset($row[$colSubfamiliaId]) ? trim((string) $row[$colSubfamiliaId]) : null;
                $erpSubfamiliaName = isset($row[$colSubfamiliaName]) ? trim((string) $row[$colSubfamiliaName]) : null;

                // Strip prefixes
                $n = fn($s) => ($stripPrefix && str_starts_with($s, $stripPrefix)) ? substr($s, strlen($stripPrefix)) : $s;
                if ($erpDeporteName) $erpDeporteName = $n($erpDeporteName);
                if ($erpCategoriaName) $erpCategoriaName = $n($erpCategoriaName);
                if ($erpFamiliaName) $erpFamiliaName = $n($erpFamiliaName);
                if ($erpSubfamiliaName) $erpSubfamiliaName = $n($erpSubfamiliaName);

                // 1. Crear Deporte
                $sportId = null;
                if ($erpDeporteId) {
                    $erpDeporteId = (int) $erpDeporteId;
                    if (!isset($sportCache[$erpDeporteId])) {
                        $sport = Sport::updateOrCreate(
                            ['erp_id' => $erpDeporteId],
                            ['name' => $erpDeporteName ?? "Deporte {$erpDeporteId}", 'available' => true]
                        );
                        $sportCache[$erpDeporteId] = $sport->id;
                    }
                    $sportId = $sportCache[$erpDeporteId];
                }

                // 2. Crear Categoría (familia padre)
                $categoryId = null;
                if ($erpFamiliaId) {
                    $erpFamiliaId = (int) $erpFamiliaId;
                    $cacheKey = "{$erpFamiliaId}";
                    if (!isset($categoryCache[$cacheKey])) {
                        $category = Category::updateOrCreate(
                            ['erp_id' => $erpFamiliaId],
                            [
                                'name' => $erpFamiliaName ?? "Familia {$erpFamiliaId}",
                                'sport_id' => $sportId,
                                'erp_sport_id' => $erpDeporteId,
                                'erp_categoria_id' => $erpCategoriaId ? (int) $erpCategoriaId : null,
                                'erp_categoria_name' => $erpCategoriaName,
                                'available' => true,
                                'last_sync_at' => now(),
                            ]
                        );
                        $categoryCache[$cacheKey] = $category->id;
                    }
                    $categoryId = $categoryCache[$cacheKey];
                }

                // 3. Crear Subfamilia si es el nivel de importación
                if ($level === 'subfamilia' && $erpSubfamiliaId) {
                    if (!$categoryId) {
                        $errors[] = "Fila {$rowNum}: Subfamilia sin familia padre.";
                        $skipped++;
                        continue;
                    }

                    $erpSubfamiliaId = (int) $erpSubfamiliaId;
                    Subfamily::updateOrCreate(
                        ['erp_id' => $erpSubfamiliaId],
                        [
                            'name' => $erpSubfamiliaName ?? "Subfamilia {$erpSubfamiliaId}",
                            'category_id' => $categoryId,
                            'available' => true,
                        ]
                    );
                    $imported++;
                } elseif ($level !== 'subfamilia') {
                    // Para otros niveles, importar como antes
                    $erpId = $erpFamiliaId ? (int) $erpFamiliaId : null;
                    $name = $erpFamiliaName;

                    if (!$erpId || !$name) {
                        $errors[] = "Fila {$rowNum}: ID o nombre vacío.";
                        $skipped++;
                        continue;
                    }

                    Category::updateOrCreate(
                        ['erp_id' => $erpId],
                        [
                            'name' => $name,
                            'sport_id' => $sportId,
                            'erp_sport_id' => $erpDeporteId,
                            'erp_categoria_id' => $erpCategoriaId ? (int) $erpCategoriaId : null,
                            'erp_categoria_name' => $erpCategoriaName,
                            'available' => true,
                            'last_sync_at' => now(),
                        ]
                    );
                    $imported++;
                }
            }

            return response()->json([
                'success'  => true,
                'message'  => "{$imported} {$level}(s) importadas." . ($skipped ? " {$skipped} omitida(s)." : ''),
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => $errors,
                'level'    => $level,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error durante la importación: ' . $e->getMessage(),
                'errors'  => [$e->getMessage()],
            ], 500);
        } finally {
            if (isset($fullPath) && file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    public function products(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $query = Product::where('category_id', $id)->with('supplier');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->paginate(20);

        return response()->json([
            'category' => ['id' => $category->id, 'name' => $category->name],
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'supplier' => $p->supplier?->label,
                'available' => $p->available,
            ]),
            'total' => $products->total(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
        ]);
    }
}
