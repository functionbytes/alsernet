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
    protected string $settingKey = 'seo.robots_txt';

    protected string $defaultRobotsTxt = <<<'ROBOTS'
User-agent: *
Allow: /

Sitemap: {sitemap_url}
ROBOTS;

    public function edit(): View
    {
        $robotsTxt = Setting::get($this->settingKey, $this->getDefaultContent());

        return view('Seo::admin.robots-txt.edit', compact('robotsTxt'));
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
            ->with('success', __('seo::robots-txt.saved_successfully'));
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
            route('sitemap.index'),
            $this->defaultRobotsTxt
        );
    }
}
