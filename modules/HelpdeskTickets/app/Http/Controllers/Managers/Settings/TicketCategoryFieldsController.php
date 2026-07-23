<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Http\Requests\Settings\ReorderTicketCategoryFieldRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreTicketCategoryFieldRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\UpdateTicketCategoryFieldRequest;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketCategoryField;

class TicketCategoryFieldsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.settings');
    }

    /**
     * List all fields for a category.
     */
    public function index(TicketCategory $category): JsonResponse
    {
        $fields = $category->fields()->ordered()->get();

        return response()->json(['success' => true, 'data' => $fields]);
    }

    /**
     * Store a new field for a category.
     */
    public function store(StoreTicketCategoryFieldRequest $request, TicketCategory $category): JsonResponse
    {
        $validated = $request->validated();

        $key = $this->resolveUniqueKey($category, $validated['key'] ?? '', $validated['label']);

        $field = $category->fields()->create([
            ...$validated,
            'key' => $key,
            'sort_order' => $category->fields()->max('sort_order') + 1,
        ]);

        return response()->json(['success' => true, 'data' => $field], 201);
    }

    /**
     * Update an existing field.
     */
    public function update(UpdateTicketCategoryFieldRequest $request, TicketCategory $category, TicketCategoryField $field): JsonResponse
    {
        $validated = $request->validated();

        $key = $this->resolveUniqueKey($category, $validated['key'] ?? '', $validated['label'], $field->id);

        $field->update([...$validated, 'key' => $key]);

        return response()->json(['success' => true, 'data' => $field->fresh()]);
    }

    /**
     * Delete a field.
     */
    public function destroy(TicketCategory $category, TicketCategoryField $field): JsonResponse
    {
        $field->delete();

        return response()->json(['success' => true, 'message' => 'Campo eliminado correctamente.']);
    }

    /**
     * Reorder fields via drag and drop.
     */
    public function reorder(ReorderTicketCategoryFieldRequest $request, TicketCategory $category): JsonResponse
    {
        $validated = $request->validated();

        foreach ($validated['items'] as $item) {
            TicketCategoryField::where('id', $item['id'])
                ->where('ticket_category_id', $category->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Generate a unique key for the field within the category.
     * Auto-generates from label if empty; appends numeric suffix if already taken.
     */
    private function resolveUniqueKey(TicketCategory $category, string $key, string $label, ?int $ignoreId = null): string
    {
        $base = $key !== '' ? $key : Str::slug($label, '_');

        $candidate = $base;
        $suffix = 2;

        while (true) {
            $exists = TicketCategoryField::where('ticket_category_id', $category->id)
                ->where('key', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists();

            if (! $exists) {
                return $candidate;
            }

            $candidate = $base.'_'.$suffix;
            $suffix++;
        }
    }
}
