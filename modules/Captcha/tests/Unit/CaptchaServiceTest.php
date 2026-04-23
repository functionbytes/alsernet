<?php

declare(strict_types=1);

namespace Modules\Captcha\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Captcha\Services\Captcha;
use Modules\Captcha\Services\CaptchaV3;
use Modules\Captcha\Tests\TestCase;
use Modules\Core\Models\Setting;

class CaptchaServiceTest extends TestCase
{
    protected function enableCaptcha(string $type = 'v2'): void
    {
        Setting::set('enable_captcha', '1');
        Setting::set('captcha_type', $type);
    }

    public function test_v2_verify_returns_true_on_successful_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'hostname' => 'localhost',
                'challenge_ts' => now()->toIso8601String(),
            ]),
        ]);

        $this->enableCaptcha('v2');
        $captcha = new Captcha('site-key', 'secret-key');

        $this->assertTrue($captcha->verify('valid-token', '127.0.0.1'));
    }

    public function test_v2_verify_returns_false_when_success_is_missing(): void
    {
        Http::fake([
            '*' => Http::response(['hostname' => 'localhost']),
        ]);

        $this->enableCaptcha('v2');
        $captcha = new Captcha('site-key', 'secret-key');

        $this->assertFalse($captcha->verify('valid-token', '127.0.0.1'));
    }

    public function test_v2_verify_returns_false_on_hostname_mismatch(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'hostname' => 'evil.com',
                'challenge_ts' => now()->toIso8601String(),
            ]),
        ]);

        $this->enableCaptcha('v2');
        $captcha = new Captcha('site-key', 'secret-key');

        $this->assertFalse($captcha->verify('valid-token', '127.0.0.1'));
    }

    public function test_v2_verify_returns_false_on_expired_challenge(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'hostname' => 'localhost',
                'challenge_ts' => now()->subMinutes(10)->toIso8601String(),
            ]),
        ]);

        $this->enableCaptcha('v2');
        $captcha = new Captcha('site-key', 'secret-key');

        $this->assertFalse($captcha->verify('valid-token', '127.0.0.1'));
    }

    public function test_v2_verify_returns_false_on_error_codes(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => false,
                'error-codes' => ['timeout-or-duplicate'],
            ]),
        ]);

        $this->enableCaptcha('v2');
        $captcha = new Captcha('site-key', 'secret-key');

        $this->assertFalse($captcha->verify('valid-token', '127.0.0.1'));
    }

    public function test_v2_verify_returns_true_when_disabled(): void
    {
        Setting::set('enable_captcha', '0');
        $captcha = new Captcha('site-key', 'secret-key');

        $this->assertTrue($captcha->verify('any-token', '127.0.0.1'));
    }

    public function test_v2_verify_returns_false_on_empty_response(): void
    {
        $this->enableCaptcha('v2');
        $captcha = new Captcha('site-key', 'secret-key');

        $this->assertFalse($captcha->verify('', '127.0.0.1'));
    }

    public function test_v2_verify_returns_false_on_invalid_token_format(): void
    {
        $this->enableCaptcha('v2');
        $captcha = new Captcha('site-key', 'secret-key');

        $this->assertFalse($captcha->verify('<script>alert(1)</script>', '127.0.0.1'));
    }

    public function test_v2_verify_works_with_null_client_ip(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'hostname' => 'localhost',
                'challenge_ts' => now()->toIso8601String(),
            ]),
        ]);

        $this->enableCaptcha('v2');
        $captcha = new Captcha('site-key', 'secret-key');

        $this->assertTrue($captcha->verify('valid-token', null));
    }

    public function test_v3_verify_returns_true_on_successful_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'hostname' => 'localhost',
                'challenge_ts' => now()->toIso8601String(),
                'score' => 0.9,
                'action' => 'form',
            ]),
        ]);

        $this->enableCaptcha('v3');
        $captcha = new CaptchaV3('site-key', 'secret-key');

        $this->assertTrue($captcha->verify('valid-token', '127.0.0.1', ['form', 0.6]));
    }

    public function test_v3_verify_returns_false_when_score_is_below_threshold(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'hostname' => 'localhost',
                'challenge_ts' => now()->toIso8601String(),
                'score' => 0.3,
                'action' => 'form',
            ]),
        ]);

        $this->enableCaptcha('v3');
        $captcha = new CaptchaV3('site-key', 'secret-key');

        $this->assertFalse($captcha->verify('valid-token', '127.0.0.1', ['form', 0.6]));
    }

    public function test_v3_verify_returns_false_on_action_mismatch(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'hostname' => 'localhost',
                'challenge_ts' => now()->toIso8601String(),
                'score' => 0.9,
                'action' => 'login',
            ]),
        ]);

        $this->enableCaptcha('v3');
        $captcha = new CaptchaV3('site-key', 'secret-key');

        $this->assertFalse($captcha->verify('valid-token', '127.0.0.1', ['form', 0.6]));
    }

    public function test_v3_verify_returns_true_when_disabled(): void
    {
        Setting::set('enable_captcha', '0');
        $captcha = new CaptchaV3('site-key', 'secret-key');

        $this->assertTrue($captcha->verify('any-token', '127.0.0.1'));
    }

    public function test_v3_verify_handles_null_client_ip(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'hostname' => 'localhost',
                'challenge_ts' => now()->toIso8601String(),
                'score' => 0.9,
                'action' => 'form',
            ]),
        ]);

        $this->enableCaptcha('v3');
        $captcha = new CaptchaV3('site-key', 'secret-key');

        $this->assertTrue($captcha->verify('valid-token', null));
    }

    public function test_display_returns_null_when_disabled(): void
    {
        Setting::set('enable_captcha', '0');
        $captcha = new Captcha('site-key', 'secret-key');

        $this->assertNull($captcha->display());
    }

    public function test_display_returns_null_without_site_key(): void
    {
        Setting::set('enable_captcha', '1');
        $captcha = new Captcha(null, 'secret-key');

        $this->assertNull($captcha->display());
    }
}
