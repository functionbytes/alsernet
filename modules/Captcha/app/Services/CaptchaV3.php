<?php

declare(strict_types=1);

namespace Modules\Captcha\Services;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Captcha\Contracts\Captcha as CaptchaContract;
use Modules\Captcha\Events\CaptchaRendered;
use Modules\Captcha\Events\CaptchaRendering;
use Modules\Core\Services\CircuitBreaker;

class CaptchaV3 extends CaptchaContract
{
    protected bool $rendered = false;

    public function verify(string $response, ?string $clientIp = null, array $options = []): bool
    {
        if (! $this->reCaptchaEnabled()) {
            return true;
        }

        if (! $this->isValidTokenFormat($response)) {
            return false;
        }

        $circuit = new CircuitBreaker('captcha.v3', 5, 60);

        if (! $circuit->isAvailable()) {
            Log::warning('Captcha circuit breaker open — verification skipped, returning false', [
                'type' => 'v3',
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
        } catch (\Throwable $e) {
            $circuit->recordFailure();
            Log::warning('Captcha V3 verification failed: '.$e->getMessage(), [
                'ip' => $clientIp,
                'type' => 'v3',
                'user_id' => auth()->id(),
                'url' => request()->url(),
            ]);

            return false;
        }

        if (! empty($data['error-codes'])) {
            Log::warning('Captcha v3 verification error codes', [
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
            Log::channel('security')->warning('Captcha v3 hostname mismatch', [
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
                Log::channel('security')->warning('Captcha v3 challenge timestamp expired', [
                    'challenge_ts' => $data['challenge_ts'],
                    'ip' => $clientIp,
                    'user_id' => auth()->id(),
                    'url' => request()->url(),
                ]);

                return false;
            }
        }

        $action = Arr::get($options, 0);
        $minScore = isset($options[1]) ? (float) $options[1] : 0.6;

        if ($action && (! isset($data['action']) || $action !== $data['action'])) {
            return false;
        }

        $score = $data['score'] ?? false;

        $valid = $score && $score >= $minScore;

        if (! $valid) {
            Log::channel('security')->info('Captcha v3 score below threshold', [
                'score' => $score,
                'threshold' => $minScore,
                'ip' => $clientIp,
                'user_id' => auth()->id(),
                'url' => request()->url(),
            ]);
        }

        return $valid;
    }

    public function display(array $attributes = ['action' => 'form'], array $options = []): ?string
    {
        if (! $this->siteKey || ! $this->reCaptchaEnabled()) {
            return null;
        }

        $name = Arr::get($options, 'name', self::RECAPTCHA_INPUT_NAME);
        $uniqueId = uniqid($name.'-');
        $headContent = $this->headRender();

        // Mark as rendered BEFORE footerRender so $isRendered is accurate inside the view.
        $this->rendered = true;

        $footerContent = $this->footerRender($uniqueId, $attributes);

        CaptchaRendering::dispatch($attributes, $options, $headContent, $footerContent);

        $captchaContent = view('captcha::v3.html', compact('name', 'uniqueId'))->render();

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
