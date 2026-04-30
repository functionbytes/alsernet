<?php

namespace Modules\Chat\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Modules\Chat\Http\Controllers\Controller;

class LocaleController extends Controller
{
    /**
     * Switch the application locale.
     */
    public function switch(Request $request, ?string $locale = null)
    {
        // Get locale from route parameter or request input
        $locale = $locale ?? $request->input('locale');

        // Validate locale - support en and es by default
        $supportedLocales = config('locales.supported', ['en' => 'English', 'es' => 'Español']);

        if (! array_key_exists($locale, $supportedLocales)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid language selection',
                ], 422);
            }

            return back()->with('error', 'Invalid language selection');
        }

        // Update user's locale preference if authenticated
        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        // Store in session
        Session::put('locale', $locale);

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Language updated successfully',
                'locale' => $locale,
            ]);
        }

        return back()->with('success', 'Language updated successfully');
    }
}
