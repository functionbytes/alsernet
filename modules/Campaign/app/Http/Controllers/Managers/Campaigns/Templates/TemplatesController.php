<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Templates;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Campaign\Models\Layout\Layout;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Models\Template\TemplateCategory;

/**
 * Slim TemplatesController.
 *
 * Editor HTML básico (textarea + iframe preview live). Categorías opcionales.
 * Soporta `copy` (clonar plantilla existente).
 */
class TemplatesController extends Controller
{
    public function index(Request $request): View
    {
        $templates = Template::query()
            ->when($request->query('q'), fn ($q, $kw) => $q->where('name', 'like', "%{$kw}%"))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('campaign::manager.templates.index', [
            'templates' => $templates,
            'categories' => TemplateCategory::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('campaign::manager.templates.create', [
            'categories' => TemplateCategory::orderBy('name')->get(),
            'layouts' => Layout::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->rules($request);

        $template = Template::create($data);

        if (! empty($data['categories'])) {
            $template->categories()->sync($data['categories']);
        }

        return redirect()
            ->route('manager.campaigns.templates.edit', $template->uid)
            ->with('success', 'Plantilla creada.');
    }

    public function edit(string $uid): View
    {
        $template = Template::where('uid', $uid)->firstOrFail();

        return view('campaign::manager.templates.edit', [
            'template' => $template,
            'categories' => TemplateCategory::orderBy('name')->get(),
            'layouts' => Layout::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $uid): RedirectResponse
    {
        $template = Template::where('uid', $uid)->firstOrFail();

        $data = $this->rules($request, true);
        $template->update($data);

        if (array_key_exists('categories', $data)) {
            $template->categories()->sync($data['categories'] ?? []);
        }

        return back()->with('success', 'Plantilla guardada.');
    }

    public function destroy(string $uid): RedirectResponse
    {
        Template::where('uid', $uid)->firstOrFail()->delete();

        return redirect()
            ->route('manager.campaigns.templates.index')
            ->with('success', 'Plantilla eliminada.');
    }

    /**
     * Devuelve el HTML del template renderizado para el iframe de preview.
     */
    public function preview(string $uid)
    {
        $template = Template::where('uid', $uid)->firstOrFail();

        return response($template->html ?: $template->content ?: '<p><em>Plantilla vacía.</em></p>')
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Clonar plantilla.
     */
    public function copy(string $uid): RedirectResponse
    {
        $template = Template::where('uid', $uid)->firstOrFail();

        $clone = $template->replicate(['uid', 'created_at', 'updated_at']);
        $clone->name = $template->name.' (copia)';
        $clone->save();

        return redirect()
            ->route('manager.campaigns.templates.edit', $clone->uid)
            ->with('success', 'Plantilla clonada.');
    }

    protected function rules(Request $request, bool $update = false): array
    {
        $required = $update ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:998'],
            'content' => ['nullable', 'string'],
            'html' => ['nullable', 'string'],
            'plain' => ['nullable', 'string'],
            'layout_id' => ['nullable', 'exists:campaign_layouts,id'],
            'shared' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:campaign_template_categories,id'],
        ]);
    }
}
