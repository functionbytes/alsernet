<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleSwitchController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $supported = array_keys(config('app.locales', ['es' => 'Español', 'en' => 'English', 'pt' => 'Português']));

        if (! in_array($locale, $supported, true)) {
            abort(404);
        }

        session(['app_locale' => $locale]);
        cookie()->queue('app_locale', $locale, 60 * 24 * 365);

        return back();
    }
}
