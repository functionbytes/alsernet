<?php

namespace Modules\Optimize\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;

class OptimizeController
{
    private const PREFIX = 'optimize.';

    private const CHECKBOXES = [
        'enabled',
        'collapse_whitespace',
        'elide_attributes',
        'inline_css',
        'insert_dns_prefetch',
        'remove_comments',
        'remove_quotes',
        'defer_javascript',
    ];

    public function index(): View
    {
        $get = fn (string $key, string $default = '0') => Setting::get(self::PREFIX.$key, $default);

        return view('optimize::settings.index', compact('get'));
    }

    public function update(Request $request): RedirectResponse
    {
        foreach (self::CHECKBOXES as $key) {
            Setting::set(self::PREFIX.$key, $request->has($key) ? '1' : '0');
        }

        Setting::clearPrefixCache('optimize.');

        return redirect()->back()->with('success', 'Configuración de optimización guardada correctamente.');
    }
}
