<?php

namespace Modules\HelpdeskChat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        // Set application locale
        App::setLocale($locale);

        // Store in session for consistency
        Session::put('locale', $locale);

        return $next($request);
    }

    /**
     * Determine the locale to use.
     */
    protected function determineLocale(Request $request): string
    {
        // 1. Check if locale is being switched via query parameter
        if ($request->has('locale') && $this->isValidLocale($request->get('locale'))) {
            return $request->get('locale');
        }

        // 2. Check authenticated user's preference
        if ($request->user() && $request->user()->locale) {
            return $request->user()->locale;
        }

        // 3. Check session
        if (Session::has('locale') && $this->isValidLocale(Session::get('locale'))) {
            return Session::get('locale');
        }

        // 4. Check account default (if user is authenticated)
        if ($request->user() && $request->user()->account && $request->user()->account->default_locale) {
            return $request->user()->account->default_locale;
        }

        // 5. Check browser's Accept-Language header
        $browserLocale = $this->getBrowserLocale($request);
        if ($browserLocale && $this->isValidLocale($browserLocale)) {
            return $browserLocale;
        }

        // 6. Fall back to application default
        return config('locales.default', config('app.locale', 'en'));
    }

    /**
     * Check if locale is valid.
     */
    protected function isValidLocale(string $locale): bool
    {
        return array_key_exists($locale, config('locales.supported', []));
    }

    /**
     * Get browser's preferred locale.
     */
    protected function getBrowserLocale(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (! $acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header
        $languages = explode(',', $acceptLanguage);
        $supportedLocales = array_keys(config('locales.supported', []));

        foreach ($languages as $language) {
            // Extract locale code (e.g., "en-US" -> "en")
            $locale = strtok(trim($language), ';-_');

            if (in_array($locale, $supportedLocales)) {
                return $locale;
            }
        }

        return null;
    }
}
