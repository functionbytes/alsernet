<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Forms\Models\Form;

class FormTemplateLibraryController extends Controller
{
    public function index(): View
    {
        $this->authorize('Forms.forms.create');

        $templates = config('forms.template_library', []);

        return view('forms::settings.forms.templates-library.index', compact('templates'));
    }

    public function preview(string $template): View
    {
        $this->authorize('Forms.forms.create');

        $templateConfig = config("forms.template_library.{$template}");

        if (! $templateConfig) {
            abort(404, 'Plantilla no encontrada.');
        }

        return view('forms::settings.forms.templates-library.preview', [
            'template' => $templateConfig,
            'templateKey' => $template,
        ]);
    }

    public function use(Request $request, string $template): RedirectResponse|JsonResponse
    {
        $this->authorize('Forms.forms.create');

        $templateConfig = config("forms.template_library.{$template}");

        if (! $templateConfig) {
            return redirect()->route('settings.forms.templates-library.index')
                ->with('error', 'La plantilla seleccionada no existe.');
        }

        $form = Form::query()->create([
            'name' => $templateConfig['name'],
            'description' => $templateConfig['description'] ?? null,
            'slug' => $this->generateUniqueSlug($templateConfig['name']),
            'success_message' => config('forms.default_success_message'),
        ]);

        foreach ($templateConfig['fields'] as $sortOrder => $fieldData) {
            $form->fields()->create(
                collect($fieldData)->merge(['sort_order' => $sortOrder])->all()
            );
        }

        session()->flash('success', "Formulario creado desde la plantilla \"{$templateConfig['name']}\".");

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => route('settings.forms.edit', $form)]);
        }

        return redirect()->route('settings.forms.edit', $form);
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Form::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
