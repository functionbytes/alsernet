<?php

declare(strict_types=1);

namespace Modules\Captcha\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Captcha\Contracts\Captcha as CaptchaContract;
use Modules\Captcha\Events\CaptchaRendered;
use Modules\Captcha\Events\CaptchaRendering;
use Modules\Core\Services\CircuitBreaker;

class Captcha extends CaptchaContract
{
    protected bool $rendered = false;

    public function display(array $attributes = [], array $options = []): ?string
    {
        if (! $this->siteKey || ! $this->reCaptchaEnabled()) {
            return null;
        }

        $name = 'captcha_'.md5(uniqid((string) random_int(0, PHP_INT_MAX), true));

        $headContent = $this->headRender();
        $footerContent = $this->footerRender($name);

        CaptchaRendering::dispatch($attributes, $options, $headContent, $footerContent);

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

        if (! $this->isValidTokenFormat($response)) {
            return false;
        }

        $circuit = new CircuitBreaker('captcha.v2', 5, 60);

        if (! $circuit->isAvailable()) {
            Log::warning('Captcha circuit breaker open — verification skipped, returning false', [
                'type' => 'v2',
                'ip' => $clientIp,
            ]);

            return false;
        }

        try {
            $http = Http::asForm();
            if (app()->isLocal()) {
                $http = $http->withoutVerifying();
            }

            $payload = [
                'secret' => $this->secretKey,
                'response' => $response,
            ];

            if ($clientIp !== null) {
                $payload['remoteip'] = $clientIp;
            }

            $httpResponse = $http->post(self::RECAPTCHA_VERIFY_API_URL, $payload);

            $circuit->recordSuccess();

            $data = $httpResponse->json() ?? [];

            if (! empty($data['error-codes'])) {
                Log::warning('Captcha v2 verification error codes', [
                    'codes' => $data['error-codes'],
                    'ip' => $clientIp,
                ]);
            }

            if (! isset($data['success']) || ! $data['success']) {
                return false;
            }

            // Validate hostname to prevent tokens generated for other domains.
            $expectedHost = request()->getHost();
            if (isset($data['hostname']) && $data['hostname'] !== $expectedHost) {
                Log::channel('security')->warning('Captcha v2 hostname mismatch', [
                    'expected' => $expectedHost,
                    'actual' => $data['hostname'],
                    'ip' => $clientIp,
                    'user_id' => auth()->id(),
                    'url' => request()->url(),
                ]);

                return false;
            }

            // Validate challenge timestamp to prevent replay attacks with old tokens.
            if (isset($data['challenge_ts'])) {
                $challengeTime = Carbon::parse($data['challenge_ts']);
                if (now()->diffInMinutes($challengeTime) > 5) {
                    Log::channel('security')->warning('Captcha v2 challenge timestamp expired', [
                        'challenge_ts' => $data['challenge_ts'],
                        'ip' => $clientIp,
                        'user_id' => auth()->id(),
                        'url' => request()->url(),
                    ]);

                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            $circuit->recordFailure();
            Log::warning('Captcha verification failed: '.$e->getMessage(), [
                'ip' => $clientIp,
                'type' => 'v2',
                'user_id' => auth()->id(),
                'url' => request()->url(),
            ]);

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
