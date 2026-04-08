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
        $this->middleware('can:Seo.robots.index')->only('edit');
        $this->middleware('can:Seo.robots.update')->only('update', 'reset');
        $this->middleware('can:Seo.robots.index')->only('testUrl');
    }

    protected string $settingKey = 'seo.robots_txt';

    protected string $defaultRobotsTxt = <<<'ROBOTS'
User-agent: *
Allow: /

Sitemap: {sitemap_url}
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

    protected function getDefaultContent(): string
    {
        return str_replace(
            '{sitemap_url}',
            route('sitemap'),
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
