<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
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

    protected function getDefaultContent(): string
    {
        return str_replace(
            '{sitemap_url}',
            route('sitemap'),
            $this->defaultRobotsTxt
        );
    }
}
