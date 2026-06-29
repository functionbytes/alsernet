<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
    public function store(Request $request, TicketCategory $category): JsonResponse
    {
        $validated = $request->validate($this->rules($category), $this->messages());

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
    public function update(Request $request, TicketCategory $category, TicketCategoryField $field): JsonResponse
    {
        $validated = $request->validate($this->rules($category, $field->id), $this->messages());

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
    public function reorder(Request $request, TicketCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer'],
        ], [
            'items.required' => 'Los elementos son obligatorios.',
            'items.array' => 'Los elementos deben ser un arreglo.',
            'items.*.id.required' => 'El identificador de cada elemento es obligatorio.',
            'items.*.sort_order.required' => 'El orden de cada elemento es obligatorio.',
        ]);

        foreach ($validated['items'] as $item) {
            TicketCategoryField::where('id', $item['id'])
                ->where('ticket_category_id', $category->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Validation rules for store/update.
     */
    private function rules(TicketCategory $category, ?int $ignoreFieldId = null): array
    {
        $uniqueKey = Rule::unique('helpdesk.helpdesk_ticket_category_fields', 'key')
            ->where('ticket_category_id', $category->id);

        if ($ignoreFieldId) {
            $uniqueKey->ignore($ignoreFieldId);
        }

        return [
            'type' => ['required', 'string', Rule::in(TicketCategoryField::TYPES)],
            'label' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'alpha_dash', 'max:100', $uniqueKey],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'default_value' => ['nullable', 'string', 'max:255'],
            'is_required' => ['boolean'],
            'is_visible' => ['boolean'],
            'width' => ['required', 'string', Rule::in(['full', 'half', 'third'])],
            'options' => ['nullable', 'array'],
            'options.*.label' => ['required_with:options', 'string', 'max:255'],
            'options.*.value' => ['required_with:options', 'string', 'max:255'],
        ];
    }

    /**
     * Spanish validation messages.
     */
    private function messages(): array
    {
        return [
            'type.required' => 'El tipo de campo es obligatorio.',
            'type.in' => 'El tipo de campo no es válido.',
            'label.required' => 'La etiqueta es obligatoria.',
            'label.max' => 'La etiqueta no puede superar los 255 caracteres.',
            'key.alpha_dash' => 'La clave solo puede contener letras, números, guiones y guiones bajos.',
            'key.max' => 'La clave no puede superar los 100 caracteres.',
            'key.unique' => 'Ya existe un campo con esta clave en la categoría.',
            'placeholder.max' => 'El marcador no puede superar los 255 caracteres.',
            'help_text.max' => 'El texto de ayuda no puede superar los 500 caracteres.',
            'width.required' => 'El ancho es obligatorio.',
            'width.in' => 'El ancho debe ser: completo, mitad o tercio.',
            'options.array' => 'Las opciones deben ser un arreglo.',
            'options.*.label.required_with' => 'Cada opción debe tener una etiqueta.',
            'options.*.value.required_with' => 'Cada opción debe tener un valor.',
        ];
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
