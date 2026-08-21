<?php

namespace Modules\Supplier\Http\Controllers\Settings\Characteristics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Supplier\Models\Characteristic\ErpCharacteristic;
use Modules\Supplier\Models\Characteristic\ErpCharacteristicValue;
use Modules\Supplier\Models\Characteristic\ModelCharacteristic;
use Modules\Supplier\Models\Characteristic\VariantCharacteristic;

class SupplierCharacteristicsController extends Controller
{
    private const VALID_TYPES = ['caracteristicas', 'valores', 'modelo', 'variante'];

    public function index(Request $request): View
    {
        $pageTitle = 'Características del ERP';
        $breadcrumb = 'Configuración / Proveedores / Características';

        $type = $request->get('type', 'caracteristicas');
        $type = in_array($type, self::VALID_TYPES, true) ? $type : 'caracteristicas';

        $search = $request->get('search');

        $perPage = (int) $request->get('per_page', 50);
        $perPage = in_array($perPage, [10, 20, 50, 100, 200], true) ? $perPage : 50;

        $items = $this->buildItemsQuery($type, $search)->paginate($perPage)->withQueryString();

        $stats = [
            'caracteristicas' => ErpCharacteristic::count(),
            'valores' => ErpCharacteristicValue::count(),
            'modelo' => ModelCharacteristic::count(),
            'variante' => VariantCharacteristic::count(),
        ];

        $lastSync = collect([
            ErpCharacteristic::max('last_sync_at'),
            ErpCharacteristicValue::max('last_sync_at'),
            ModelCharacteristic::max('last_sync_at'),
            VariantCharacteristic::max('last_sync_at'),
        ])->filter()->max();

        return view('supplier::settings.views.characteristics.index', compact(
            'type', 'search', 'perPage', 'items', 'stats', 'lastSync', 'pageTitle', 'breadcrumb'
        ));
    }

    private function buildItemsQuery(string $type, ?string $search)
    {
        return match ($type) {
            'valores' => ErpCharacteristicValue::query()
                ->with('characteristic')
                ->when($search, fn ($q, $search) => $q->where('nombre', 'like', "%{$search}%"))
                ->orderBy('nombre'),

            'modelo' => ModelCharacteristic::query()
                ->with('characteristic')
                ->when($search, fn ($q, $search) => $q->whereHas('characteristic', fn ($q) => $q->where('nombre', 'like', "%{$search}%")))
                ->orderBy('erp_model_id'),

            'variante' => VariantCharacteristic::query()
                ->with(['characteristic', 'value'])
                ->when($search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                    $q->whereHas('characteristic', fn ($q) => $q->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('value', fn ($q) => $q->where('nombre', 'like', "%{$search}%"));
                }))
                ->orderBy('erp_article_id'),

            default => ErpCharacteristic::query()
                ->when($search, fn ($q, $search) => $q->where('nombre', 'like', "%{$search}%"))
                ->orderBy('nombre'),
        };
    }
}
