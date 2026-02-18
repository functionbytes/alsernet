<?php

namespace Modules\Template\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;

class ThemeOptionController
{
    private const PREFIX = 'theme.option.';

    private static array $repeatableKeys = [
        'social_links',
        'header_messages',
        'contact_boxes',
    ];

    public function index(): View
    {
        $o = fn (string $key, string $default = '') => Setting::get(self::PREFIX.$key, $default);

        return view('template::theme-options.index', [
            'o' => $o,
            'socialLinks' => json_decode($o('social_links', '[]'), true) ?: [],
            'headerMessages' => json_decode($o('header_messages', '[]'), true) ?: [],
            'contactBoxes' => json_decode($o('contact_boxes', '[]'), true) ?: [],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            if (in_array($key, self::$repeatableKeys)) {
                Setting::set(self::PREFIX.$key, json_encode($value));
            } else {
                Setting::set(self::PREFIX.$key, $value ?? '');
            }
        }

        // Clear cache for all theme options
        Setting::clearPrefixCache('theme.option');

        return redirect()
            ->back()
            ->with('success', 'Opciones del tema actualizadas correctamente.');
    }
}
