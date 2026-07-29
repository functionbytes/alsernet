<?php

namespace Modules\Supplier\Services\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ContentFilterService
{
    /**
     * Apply all filters from the request to the AiContent query builder.
     * Centralizes filter logic used in SupplierContentController index/getData.
     *
     * @param  array<int, string>  $searchFields  Columns to search with LIKE
     */
    public function apply(Builder $query, Request $request, array $searchFields = ['generated_name', 'short_description', 'model_id']): Builder
    {
        $searchValue = $request->input('search.value') ?? $request->input('search');
        if (is_string($searchValue) && $searchValue !== '') {
            $query->where(function (Builder $q) use ($searchValue, $searchFields) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', "%{$searchValue}%");
                }
                $q->orWhere('erp_reference', 'like', "%{$searchValue}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($sportId = $request->input('sport_id')) {
            $query->whereHas('supplierProduct.category', function (Builder $q) use ($sportId) {
                $q->where('sport_id', $sportId);
            });
        }

        if ($erpCatId = $request->input('erp_categoria_id')) {
            $query->whereHas('supplierProduct.category', function (Builder $q) use ($erpCatId) {
                $q->where('erp_categoria_id', $erpCatId);
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->whereHas('supplierProduct', function (Builder $q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        if ($subfamilyId = $request->input('subfamily_id')) {
            $query->whereHas('supplierProduct', function (Builder $q) use ($subfamilyId) {
                $q->where('subfamily_id', $subfamilyId);
            });
        }

        if ($promptId = $request->input('prompt_id')) {
            $query->where('prompt_id', $promptId);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }

        if ($dateTo = $request->input('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        match ($request->input('content_type')) {
            'name' => $query->whereNotNull('generated_name'),
            'description' => $query->whereNotNull('short_description'),
            'seo' => $query->whereNotNull('seo_title'),
            default => null,
        };

        // Filtro: contenidos generados hace más de N días
        if ($days = $request->input('older_than_days')) {
            $query->where('created_at', '<=', now()->subDays((int) $days));
        }

        // Filtro: solo contenidos sin fuentes web consultadas
        if ($request->boolean('no_sources')) {
            $query->where(function (Builder $q) {
                $q->whereNull('sources_used')
                  ->orWhereJsonLength('sources_used', 0);
            });
        }

        // Filtro: solo fallidos (para retry rápido desde la URL)
        if ($request->input('status') === 'failed') {
            // ya cubierto por el filtro de status de arriba
        }

        return $query;
    }
}
