<?php

namespace Modules\Faqs\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqSettingsController extends Controller
{
    public function index(): View
    {
        $settings = [
            'faqs_enabled' => setting('faqs.enabled', '0'),
        ];

        return view('faqs::settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        updateSettings([
            'faqs.enabled' => $request->has('faqs_enabled') ? '1' : '0',
        ]);

        return redirect()->route('settings.faqs.index')->with('success', 'Ajustes guardados exitosamente.');
    }
}
