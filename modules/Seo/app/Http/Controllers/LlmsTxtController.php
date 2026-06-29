<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Setting;
use Modules\Seo\Http\Requests\UpdateLlmsTxtRequest;
use Modules\Seo\Models\SeoMeta;

/**
 * Manages the llms.txt file: a machine-readable descriptor for AI crawlers
 * (ChatGPT, Claude, Perplexity, Gemini) that points them at the most relevant
 * content of the site. Complements robots.txt for the LLM-era web.
 *
 * Spec: https://llmstxt.org
 */
class LlmsTxtController extends Controller
{
    protected string $settingKey = 'seo.llms_txt';

    public function __construct()
    {
        $this->middleware('can:Seo.llms.index')->only('edit');
        $this->middleware('can:Seo.llms.update')->only('update', 'reset');
    }

    public function edit(): View
    {
        $llmsTxt = Setting::get($this->settingKey, $this->getDefaultContent());

        return view('Seo::settings.llms-txt.edit', compact('llmsTxt'));
    }

    public function update(UpdateLlmsTxtRequest $request): RedirectResponse
    {
        Setting::set($this->settingKey, $request->input('llms_txt', ''));

        return redirect()->back()->with('success', 'llms.txt guardado correctamente.');
    }

    public function reset(): RedirectResponse
    {
        Setting::set($this->settingKey, $this->getDefaultContent());

        return redirect()->back()->with('success', 'llms.txt restaurado al contenido por defecto.');
    }

    public function serve(): Response
    {
        if (! config('seohelper.llms_txt.enabled', true)) {
            abort(404);
        }

        $ttl = (int) config('seohelper.llms_txt.cache_ttl', 3600);

        $content = Cache::remember(
            'seo:llms_txt:serve',
            $ttl,
            fn () => Setting::get($this->settingKey) ?: $this->getDefaultContent()
        );

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age='.$ttl,
        ]);
    }

    protected function getDefaultContent(): string
    {
        $appName = config('app.name', 'Sitio');
        $appUrl = rtrim((string) config('app.url', ''), '/');
        $description = config('seohelper.llms_txt.intro')
            ?: config('Seo.default_description', 'Sitio web');
        $sitemapUrl = $appUrl ? $appUrl.'/sitemap.xml' : '/sitemap.xml';

        $lines = [
            '# '.$appName,
            '',
            '> '.$description,
            '',
            '## Recursos principales',
            '',
            '- [Página principal]('.($appUrl ?: '/').'): Inicio del sitio.',
            '- [Sitemap XML]('.$sitemapUrl.'): Listado completo de URLs indexables.',
        ];

        $top = $this->topScoringPages();

        if ($top->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '## Páginas destacadas';
            $lines[] = '';
            foreach ($top as $meta) {
                $title = $this->sanitize((string) ($meta->title ?? 'Untitled'));
                $url = (string) $meta->canonical_url;
                $desc = $this->sanitize((string) ($meta->description ?? ''));
                $line = '- ['.$title.']('.$url.')';
                if ($desc !== '') {
                    $line .= ': '.$desc;
                }
                $lines[] = $line;
            }
        }

        $lines[] = '';
        $lines[] = '## Política de uso por IA';
        $lines[] = '';
        $lines[] = 'Este sitio permite indexación por motores de IA con atribución. Para uso';
        $lines[] = 'comercial o entrenamiento de modelos, consulta la política en /privacidad.';

        return implode("\n", $lines)."\n";
    }

    /**
     * @return Collection<int, SeoMeta>
     */
    private function topScoringPages(): Collection
    {
        $minScore = (int) config('seohelper.llms_txt.min_score', 70);
        $maxItems = (int) config('seohelper.llms_txt.max_items', 50);

        return SeoMeta::query()
            ->whereNotNull('canonical_url')
            ->where(function ($q) {
                $q->whereNull('robots')->orWhere('robots', 'not like', '%noindex%');
            })
            ->when($minScore > 0, fn ($q) => $q->where('seo_score', '>=', $minScore))
            ->orderByDesc('seo_score')
            ->orderByDesc('updated_at')
            ->limit(max(1, $maxItems))
            ->get(['title', 'description', 'canonical_url']);
    }

    private function sanitize(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = str_replace(['[', ']', '(', ')'], '', $value);

        return trim($value);
    }
}
