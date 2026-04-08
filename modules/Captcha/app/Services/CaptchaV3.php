<?php

namespace Modules\Captcha\Services;

use App\Services\CircuitBreaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Captcha\Contracts\Captcha as CaptchaContract;
use Modules\Captcha\Events\CaptchaRendered;
use Modules\Captcha\Events\CaptchaRendering;

class CaptchaV3 extends CaptchaContract
{
    protected bool $rendered = false;

    public function verify(string $response, string $clientIp, array $options = []): bool
    {
        if (! $this->reCaptchaEnabled()) {
            return true;
        }

        $circuit = new CircuitBreaker('captcha', 5, 60);

        if (! $circuit->isAvailable()) {
            Log::warning('Captcha circuit breaker open — verification skipped, returning false');

            return false;
        }

        try {
            $httpResponse = Http::asForm()
                ->withoutVerifying()
                ->post(self::RECAPTCHA_VERIFY_API_URL, [
                    'secret' => $this->secretKey,
                    'response' => $response,
                    'remoteip' => $clientIp,
                ]);

            $circuit->recordSuccess();

            $data = $httpResponse->json();
        } catch (\Throwable $e) {
            $circuit->recordFailure();
            Log::warning('Captcha V3 verification failed: '.$e->getMessage());

            return false;
        }

        if (! isset($data['success']) || ! $data['success']) {
            return false;
        }

        $action = $options[0];
        $minScore = isset($options[1]) ? (float) $options[1] : 0.6;

        if ($action && (! isset($data['action']) || $action != $data['action'])) {
            return false;
        }

        $score = $data['score'] ?? false;

        return $score && $score >= $minScore;
    }

    public function display(array $attributes = ['action' => 'form'], array $options = []): ?string
    {
        if (! $this->siteKey || ! $this->reCaptchaEnabled()) {
            return null;
        }

        $name = Arr::get($options, 'name', self::RECAPTCHA_INPUT_NAME);
        $uniqueId = uniqid($name.'-');
        $headContent = $this->headRender();
        $footerContent = $this->footerRender($uniqueId, $attributes);

        CaptchaRendering::dispatch($attributes, $options, $headContent, $footerContent);

        // NOTE: add_filter() is a Botble CMS function, not available in Laravel.
        if (function_exists('add_filter')) {
            if (defined('THEME_FRONT_FOOTER')) {
                add_filter(THEME_FRONT_FOOTER, function (?string $html) use ($headContent, $footerContent): string {
                    return $html.$headContent.$footerContent;
                }, 99);
            }

            if (defined('BASE_FILTER_FOOTER_LAYOUT_TEMPLATE')) {
                add_filter(BASE_FILTER_FOOTER_LAYOUT_TEMPLATE, function (?string $html) use ($headContent, $footerContent): string {
                    return $html.$headContent.$footerContent;
                }, 99);
            }
        }

        $captchaContent = view('captcha::v3.html', compact('name', 'uniqueId'))->render();

        $this->rendered = true;

        if (request()->ajax() || request()->wantsJson()) {
            $captchaContent .= $footerContent;
        }

        return tap(
            $captchaContent,
            fn (string $rendered) => CaptchaRendered::dispatch($rendered)
        );
    }

    protected function headRender(): string
    {
        return view('captcha::v3.head')->render();
    }

    protected function footerRender(string $uniqueId, array $attributes): string
    {
        $action = Arr::get($attributes, 'action', 'form');
        $isRendered = $this->rendered;

        $url = self::RECAPTCHA_CLIENT_API_URL.'?'.http_build_query([
            'onload' => 'onloadCallback',
            'render' => $this->siteKey,
            'hl' => app()->getLocale(),
        ]);

        return view('captcha::v3.script', [
            'siteKey' => $this->siteKey,
            'id' => $uniqueId,
            'action' => $action,
            'url' => $url,
            'isRendered' => $isRendered,
        ])->render();
    }
}
