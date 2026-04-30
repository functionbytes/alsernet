<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Templates;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Campaign\Models\CampaignSubscriber;
use Modules\Campaign\Models\Layout\Layout;
use Modules\Campaign\Models\Template\Template;
use Modules\Campaign\Models\Template\TemplateCategory;
use Modules\Campaign\Services\LinkRotChecker;
use Modules\Campaign\Services\SpamScoreChecker;
use Modules\Campaign\Services\TemplateSanitizer;

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
     * Si se pasa ?subscriber_uid= usa los datos reales de ese suscriptor.
     */
    public function preview(Request $request, string $uid)
    {
        $template = Template::where('uid', $uid)->firstOrFail();
        $html = $template->html ?: $template->content ?: '<p><em>Plantilla vacía.</em></p>';

        if ($request->has('subscriber_uid')) {
            $subscriber = CampaignSubscriber::where('uid', $request->query('subscriber_uid'))->first();
            if ($subscriber) {
                $html = $this->renderWithSubscriber($html, $subscriber);
            }
        }

        return response($html)
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Valida una plantilla y devuelve problemas detectados.
     */
    public function validateTemplate(Request $request, string $uid): JsonResponse
    {
        $template = Template::where('uid', $uid)->firstOrFail();
        $html = $request->input('html', $template->html ?: $template->content ?: '');
        $result = TemplateSanitizer::validate($html);

        // Spam score heurístico
        $spamChecker = new SpamScoreChecker;
        $spamResult = $spamChecker->score($html, $template->subject);

        // Link rot check
        $linkChecker = new LinkRotChecker;
        $brokenLinks = $linkChecker->check($html);

        return response()->json([
            'validation' => $result,
            'spam_score' => $spamResult,
            'links' => [
                'total' => count($brokenLinks),
                'broken' => collect($brokenLinks)->filter(fn ($r) => ! $r['ok'])->values()->all(),
            ],
        ]);
    }

    protected function renderWithSubscriber(string $html, CampaignSubscriber $subscriber): string
    {
        $replacements = [
            '{{SUBSCRIBER_EMAIL}}' => $subscriber->email,
            '{{SUBSCRIBER_UID}}' => $subscriber->uid,
        ];

        foreach ($subscriber->attributes ?? [] as $key => $value) {
            $replacements['{{SUBSCRIBER_'.strtoupper($key).'}}'] = $value;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $html);
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

        $data = $request->validate([
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

        if (! empty($data['content']) && ! $request->boolean('builder')) {
            $data['content'] = TemplateSanitizer::purify($data['content']);
        }

        return $data;
    }
}
