<?php

namespace Modules\Optimize\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class PageSpeed
{
    abstract public function apply(string $buffer): string;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldProcess($request, $response)) {
            return $response;
        }

        return $response->setContent($this->apply($response->getContent()));
    }

    protected function replace(array $replace, string $buffer): string
    {
        return preg_replace(array_keys($replace), array_values($replace), $buffer);
    }

    protected function shouldProcess(Request $request, mixed $response): bool
    {
        if (Setting::get('optimize.enabled', '0') !== '1') {
            return false;
        }

        if ($request->is('setting*')) {
            return false;
        }

        if ($request->expectsJson()) {
            return false;
        }

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        foreach (config('Optimize.general.skip', []) as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        return true;
    }
}
