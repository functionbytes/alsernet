<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DeepLTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Forms\Http\Requests\StoreFormFieldRequest;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;

class FormFieldController extends Controller
{
    public function index(Form $form): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $fields = $form->fields()->ordered()->get();

        return response()->json([
            'success' => true,
            'fields' => $fields,
        ]);
    }

    public function store(StoreFormFieldRequest $request, Form $form): JsonResponse
    {
        $data = $request->validated();

        $data['form_id'] = $form->id;
        $data['key'] = $this->resolveUniqueKey($request, $form);
        $data['name'] = $data['key'];

        if (in_array($data['type'], FormField::LAYOUT_TYPES, true)) {
            $data['is_required'] = false;
        }

        $data['sort_order'] = $form->fields()->max('sort_order') + 1;

        $field = FormField::query()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Campo creado correctamente.',
            'field' => $field,
        ], 201);
    }

    public function edit(Form $form, FormField $field): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        if ($field->form_id !== $form->id) {
            return response()->json(['success' => false, 'message' => 'Campo no encontrado.'], 404);
        }

        return response()->json([
            'success' => true,
            'field' => $field,
        ]);
    }

    public function update(Request $request, Form $form, FormField $field): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        if ($field->form_id !== $form->id) {
            return response()->json(['success' => false, 'message' => 'Campo no encontrado.'], 404);
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'default_value' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'options.*.value' => ['required_with:options', 'string'],
            'options.*.label' => ['required_with:options', 'string'],
            'validation_rules' => ['nullable', 'array'],
            'is_required' => ['boolean'],
            'width' => ['nullable', 'in:full,half,third,quarter'],
            'step_number' => ['nullable', 'integer', 'min:1'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
            'step_value' => ['nullable', 'numeric'],
            'html_content' => ['nullable', 'string'],
            'consent_text' => ['nullable', 'string'],
            'formula' => ['nullable', 'string', 'max:500'],
            'likert_rows' => ['nullable', 'array'],
            'show_char_counter' => ['boolean'],
            'label_position' => ['nullable', 'in:top,floating,hidden'],
            'css_class' => ['nullable', 'string', 'max:255'],
            'translations' => ['nullable', 'array'],
            'translations.*' => ['nullable', 'array'],
            'translations.*.label' => ['nullable', 'string', 'max:255'],
            'translations.*.placeholder' => ['nullable', 'string', 'max:255'],
            'translations.*.consent_text' => ['nullable', 'string'],
            'conditions' => ['nullable', 'array'],
            'conditions.*.field' => ['required_with:conditions', 'string'],
            'conditions.*.operator' => ['required_with:conditions', 'string', 'in:equals,not_equals,contains,not_empty,empty,greater_than,less_than'],
            'conditions.*.value' => ['nullable', 'string'],
            'conditions.*.logic' => ['nullable', 'string', 'in:OR'],
        ]);

        if ($field->isLayoutField()) {
            $data['is_required'] = false;
        }

        $field->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Campo actualizado correctamente.',
            'field' => $field->fresh(),
        ]);
    }

    public function destroy(Form $form, FormField $field): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        if ($field->form_id !== $form->id) {
            return response()->json(['success' => false, 'message' => 'Campo no encontrado.'], 404);
        }

        if ($field->submissionValues()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el campo porque tiene datos de envíos asociados.',
            ], 422);
        }

        $field->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campo eliminado correctamente.',
        ]);
    }

    public function reorder(Request $request, Form $form): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $formFieldIds = $form->fields()->pluck('id')->all();

        foreach ($request->input('items') as $item) {
            if (! in_array($item['id'], $formFieldIds, true)) {
                continue;
            }

            FormField::query()->where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    public function duplicate(Form $form, FormField $field): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        if ($field->form_id !== $form->id) {
            return response()->json(['success' => false, 'message' => 'Campo no encontrado.'], 404);
        }

        $cloned = $field->replicate();
        $cloned->key = $this->resolveUniqueKey($field->key.'_copy', $form);
        $cloned->sort_order = $form->fields()->max('sort_order') + 1;
        $cloned->save();

        return response()->json([
            'success' => true,
            'message' => 'Campo duplicado correctamente.',
            'field' => $cloned,
        ], 201);
    }

    public function translateWithDeepL(Form $form, FormField $field): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        if ($field->form_id !== $form->id) {
            return response()->json(['success' => false, 'message' => 'Campo no encontrado.'], 404);
        }

        $deepl = app(DeepLTranslationService::class);

        if (! $deepl->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'DeepL no está configurado. Añade DEEPL_API_KEY.'], 422);
        }

        $locales = DB::table('locales')
            ->where('is_active', true)
            ->where('code', '!=', 'es')
            ->pluck('code')
            ->all();

        if (empty($locales)) {
            return response()->json(['success' => false, 'message' => 'No hay idiomas activos configurados.'], 422);
        }

        $existing = $field->translations ?? [];
        $errors = [];

        foreach ($locales as $locale) {
            try {
                $texts = [
                    $field->label ?? '',
                    $field->placeholder ?? '',
                    $field->consent_text ?? '',
                ];

                $translated = $deepl->translate($texts, $locale, 'es');

                $localeData = array_filter([
                    'label' => $translated[0] ?: null,
                    'placeholder' => $translated[1] ?: null,
                    'consent_text' => $translated[2] ?: null,
                ]);

                if (! empty($localeData)) {
                    $existing[$locale] = array_merge($existing[$locale] ?? [], $localeData);
                }
            } catch (\RuntimeException $e) {
                $errors[$locale] = $e->getMessage();
            }
        }

        $field->update(['translations' => $existing]);

        return response()->json([
            'success' => empty($errors),
            'translations' => $existing,
            'errors' => $errors,
            'message' => empty($errors)
                ? 'Traducciones generadas correctamente.'
                : 'Traducción completada con algunos errores.',
        ]);
    }

    private function resolveUniqueKey(StoreFormFieldRequest|string $requestOrBase, Form $form): string
    {
        $base = is_string($requestOrBase)
            ? $requestOrBase
            : ($requestOrBase->filled('key')
                ? $requestOrBase->input('key')
                : Str::slug($requestOrBase->input('label'), '_'));

        $key = $base;
        $counter = 1;

        while (FormField::query()->where('form_id', $form->id)->where('key', $key)->exists()) {
            $key = $base.'_'.$counter++;
        }

        return $key;
    }
}
