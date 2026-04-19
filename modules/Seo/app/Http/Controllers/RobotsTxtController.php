<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Core\Models\Setting;

class RobotsTxtController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.robots.index')->only('edit', 'testUrl', 'aiCrawlersStatus');
        $this->middleware('can:Seo.robots.update')->only('update', 'reset');
    }

    protected string $settingKey = 'seo.robots_txt';

    protected string $defaultRobotsTxt = <<<'ROBOTS'
User-agent: *
Allow: /

# AI crawlers — ajusta Allow/Disallow según tu política de contenido IA
User-agent: GPTBot
Allow: /

User-agent: ChatGPT-User
Allow: /

User-agent: OAI-SearchBot
Allow: /

User-agent: ClaudeBot
Allow: /

User-agent: anthropic-ai
Allow: /

User-agent: PerplexityBot
Allow: /

User-agent: Google-Extended
Allow: /

User-agent: CCBot
Disallow: /

Sitemap: {sitemap_url}
Sitemap: {sitemap_index_url}
ROBOTS;

    public function edit(): View
    {
        $robotsTxt = Setting::get($this->settingKey, $this->getDefaultContent());

        return view('Seo::settings.robots-txt.edit', compact('robotsTxt'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'robots_txt' => 'nullable|string',
        ]);

        $robotsTxt = $request->input('robots_txt', '');

        Setting::set($this->settingKey, $robotsTxt);

        return redirect()
            ->back()
            ->with('success', 'Robots.txt guardado correctamente');
    }

    public function reset(): RedirectResponse
    {
        Setting::set($this->settingKey, $this->getDefaultContent());

        return redirect()->back()->with('success', 'Robots.txt restaurado al contenido por defecto.');
    }

    public function serve(): Response
    {
        $robotsTxt = Setting::get($this->settingKey, $this->getDefaultContent());

        return response($robotsTxt, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    public function testUrl(Request $request): JsonResponse
    {
        $request->validate(['url' => 'required|url|max:500']);

        $url = $request->input('url');
        $robotsContent = Setting::get($this->settingKey, $this->getDefaultContent());
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $result = $this->isBlocked($robotsContent, $path);

        return response()->json([
            'url' => $url,
            'path' => $path,
            'is_blocked' => $result['blocked'],
            'matching_rule' => $result['rule'],
            'user_agent' => $result['user_agent'],
        ]);
    }

    /**
     * Reporta el estado de los AI crawlers conocidos respecto al robots.txt
     * actual. Facilita detectar bots bloqueados por error y decidir política.
     */
    public function aiCrawlersStatus(): JsonResponse
    {
        $robotsContent = Setting::get($this->settingKey, $this->getDefaultContent());
        $bots = (array) config('seohelper.ai_crawlers', []);

        $status = [];

        foreach ($bots as $name => $description) {
            $state = $this->userAgentState($robotsContent, (string) $name);

            $status[] = [
                'bot' => $name,
                'description' => $description,
                'declared' => $state['declared'],
                'allowed_root' => $state['allowed_root'],
                'disallow_rules' => $state['disallow_rules'],
                'note' => $state['declared']
                    ? ($state['allowed_root'] ? 'Permitido en /' : 'Bloqueado por Disallow')
                    : 'No declarado (hereda User-agent: *)',
            ];
        }

        return response()->json([
            'total_bots' => count($status),
            'bots' => $status,
        ]);
    }

    /**
     * @return array{declared: bool, allowed_root: bool, disallow_rules: array<int, string>}
     */
    private function userAgentState(string $robotsContent, string $userAgent): array
    {
        $lines = explode("\n", $robotsContent);
        $declared = false;
        $allowedRoot = true;
        $disallowRules = [];
        $inBlock = false;
        $targetLower = strtolower($userAgent);

        foreach ($lines as $line) {
            $line = trim($line);

            if (str_starts_with(strtolower($line), 'user-agent:')) {
                $agent = strtolower(trim(substr($line, 11)));
                $inBlock = ($agent === $targetLower);
                if ($inBlock) {
                    $declared = true;
                }

                continue;
            }

            if (! $inBlock) {
                continue;
            }

            if (str_starts_with(strtolower($line), 'disallow:')) {
                $rule = trim(substr($line, 9));
                if ($rule !== '') {
                    $disallowRules[] = $rule;
                    if ($rule === '/') {
                        $allowedRoot = false;
                    }
                }
            }
        }

        return [
            'declared' => $declared,
            'allowed_root' => $allowedRoot,
            'disallow_rules' => $disallowRules,
        ];
    }

    protected function getDefaultContent(): string
    {
        return str_replace(
            ['{sitemap_url}', '{sitemap_index_url}'],
            [url('/sitemap.xml'), url('/sitemap-index.xml')],
            $this->defaultRobotsTxt
        );
    }

    private function isBlocked(string $robotsContent, string $path): array
    {
        $lines = explode("\n", $robotsContent);
        $currentAgent = '*';
        $matchingRule = null;
        $blocked = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if (str_starts_with(strtolower($line), 'user-agent:')) {
                $currentAgent = trim(substr($line, 11));
            } elseif (str_starts_with(strtolower($line), 'disallow:')) {
                $rule = trim(substr($line, 9));
                if (! empty($rule) && str_starts_with($path, $rule)) {
                    $blocked = true;
                    $matchingRule = "Disallow: $rule";
                    break;
                }
            } elseif (str_starts_with(strtolower($line), 'allow:')) {
                $rule = trim(substr($line, 6));
                if (! empty($rule) && str_starts_with($path, $rule)) {
                    $blocked = false;
                    $matchingRule = "Allow: $rule";
                }
            }
        }

        return ['blocked' => $blocked, 'rule' => $matchingRule, 'user_agent' => $currentAgent];
    }
}
