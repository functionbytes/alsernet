<?php

namespace Modules\Supplier\Traits;

use Illuminate\Http\RedirectResponse;

trait HasFlashMessages
{
    protected function flashSuccess(string $message, ?string $route = null, array $params = []): RedirectResponse
    {
        $response = $route !== null
            ? redirect()->route($route, $params)
            : back();

        return $response->with('success', $message);
    }

    protected function flashError(string $message, ?string $route = null, array $params = []): RedirectResponse
    {
        $response = $route !== null
            ? redirect()->route($route, $params)
            : back();

        return $response->with('error', $message);
    }
}
