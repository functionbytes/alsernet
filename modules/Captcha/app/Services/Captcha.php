<?php

namespace Modules\Captcha\Services;

use App\Services\CircuitBreaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Captcha\Contracts\Captcha as CaptchaContract;
use Modules\Captcha\Events\CaptchaRendered;
use Modules\Captcha\Events\CaptchaRendering;

class Captcha extends CaptchaContract
{
    protected bool $rendered = false;

    public function display(array $attributes = [], array $options = []): ?string
    {
        if (! $this->siteKey || ! $this->reCaptchaEnabled()) {
            return null;
        }

        $name = 'captcha_'.md5(uniqid((string) rand(), true));

        $headContent = $this->headRender();
        $footerContent = $this->footerRender($name);

        CaptchaRendering::dispatch($attributes, $options, $headContent, $footerContent);

        // NOTE: add_filter() is a Botble CMS function, not available in Laravel.
        if (function_exists('add_filter')) {
            add_filter(['theme-front-header', 'ecommerce_checkout_header'], function ($html) use ($headContent) {
                return $html.$headContent;
            }, 299);

            add_filter(['theme-front-footer', 'ecommerce_checkout_footer'], function (?string $html) use ($footerContent): string {
                return $html.$footerContent;
            }, 99);

            if (defined('BASE_FILTER_HEAD_LAYOUT_TEMPLATE')) {
                add_filter(BASE_FILTER_HEAD_LAYOUT_TEMPLATE, function ($html) use ($headContent) {
                    return $html.$headContent;
                }, 299);
            }

            if (defined('BASE_FILTER_FOOTER_LAYOUT_TEMPLATE')) {
                add_filter(BASE_FILTER_FOOTER_LAYOUT_TEMPLATE, function (?string $html) use ($footerContent): string {
                    return $html.$footerContent;
                }, 99);
            }
        }

        $this->rendered = true;

        $captchaContent = view('captcha::v2.html', ['name' => $name, 'siteKey' => $this->siteKey])->render();

        if (! function_exists('add_filter') || request()->ajax()) {
            $captchaContent .= $headContent.$footerContent;
        }

        return tap(
            $captchaContent,
            fn (string $rendered) => CaptchaRendered::dispatch($rendered)
        );
    }

    public function verify(string $response, ?string $clientIp = null, array $options = []): bool
    {
        if (! $this->reCaptchaEnabled()) {
            return true;
        }

        if (empty($response)) {
            return false;
        }

        $circuit = new CircuitBreaker('captcha', 5, 60);

        if (! $circuit->isAvailable()) {
            Log::warning('Captcha circuit breaker open — verification skipped, returning false');

            return false;
        }

        try {
            $http = Http::asForm();
            if (app()->isLocal()) {
                $http = $http->withoutVerifying();
            }

            $httpResponse = $http->post(self::RECAPTCHA_VERIFY_API_URL, [
                'secret' => $this->secretKey,
                'response' => $response,
                'remoteip' => $clientIp,
            ]);

            $circuit->recordSuccess();

            return $httpResponse->json('success');
        } catch (\Throwable $e) {
            $circuit->recordFailure();
            Log::warning('Captcha verification failed: '.$e->getMessage());

            return false;
        }
    }

    protected function headRender(): string
    {
        return view('captcha::header-meta')->render();
    }

    protected function footerRender(string $name): string
    {
        $isRendered = $this->rendered;

        $url = self::RECAPTCHA_CLIENT_API_URL.'?'.http_build_query([
            'onload' => 'onloadCallback',
            'render' => 'explicit',
            'hl' => app()->getLocale(),
        ]);

        return view('captcha::v2.script', compact('url', 'isRendered', 'name'))->render();
    }
}
