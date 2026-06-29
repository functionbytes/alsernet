<?php

declare(strict_types=1);

namespace Modules\Captcha\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Modules\Captcha\Tests\TestCase;
use Modules\Core\Models\Setting;

class CaptchaValidationRuleTest extends TestCase
{
    protected function enableCaptcha(string $type = 'v2'): void
    {
        Setting::set('enable_captcha', '1');
        Setting::set('captcha_type', $type);
    }

    public function test_captcha_rule_passes_when_disabled(): void
    {
        Setting::set('enable_captcha', '0');

        $validator = Validator::make(
            ['g-recaptcha-response' => ''],
            ['g-recaptcha-response' => 'captcha']
        );

        $this->assertFalse($validator->fails());
    }

    public function test_captcha_rule_fails_on_empty_response_when_enabled(): void
    {
        $this->enableCaptcha('v2');

        $validator = Validator::make(
            ['g-recaptcha-response' => ''],
            ['g-recaptcha-response' => 'captcha']
        );

        $this->assertTrue($validator->fails());
    }

    public function test_captcha_rule_passes_with_valid_v2_token(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'hostname' => 'localhost',
                'challenge_ts' => now()->toIso8601String(),
            ]),
        ]);

        $this->enableCaptcha('v2');

        $validator = Validator::make(
            ['g-recaptcha-response' => 'valid-token-from-google'],
            ['g-recaptcha-response' => 'captcha']
        );

        $this->assertFalse($validator->fails());
    }

    public function test_captcha_rule_fails_with_invalid_v2_token(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => false,
                'error-codes' => ['bad-request'],
            ]),
        ]);

        $this->enableCaptcha('v2');

        $validator = Validator::make(
            ['g-recaptcha-response' => 'invalid-token'],
            ['g-recaptcha-response' => 'captcha']
        );

        $this->assertTrue($validator->fails());
    }

    public function test_captcha_rule_passes_with_valid_v3_token(): void
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
        Setting::set('recaptcha_score', '0.5');

        $validator = Validator::make(
            ['g-recaptcha-response' => 'valid-v3-token'],
            ['g-recaptcha-response' => 'captcha:form,0.6']
        );

        $this->assertFalse($validator->fails());
    }

    public function test_captcha_rule_fails_when_v3_score_is_low(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'hostname' => 'localhost',
                'challenge_ts' => now()->toIso8601String(),
                'score' => 0.2,
                'action' => 'form',
            ]),
        ]);

        $this->enableCaptcha('v3');

        $validator = Validator::make(
            ['g-recaptcha-response' => 'low-score-token'],
            ['g-recaptcha-response' => 'captcha:form,0.6']
        );

        $this->assertTrue($validator->fails());
    }

    public function test_math_captcha_rule_passes_with_correct_answer(): void
    {
        $mathCaptcha = app('math-captcha');
        $mathCaptcha->label(); // Generate question in session

        $label = $mathCaptcha->getMathLabelOnly();
        preg_match('/(\d+)\s+([+\-*])\s+(\d+)/', $label, $matches);

        $first = (int) $matches[1];
        $operand = $matches[2];
        $second = (int) $matches[3];

        $result = match ($operand) {
            '+' => $first + $second,
            '*' => $first * $second,
            '-' => abs($first - $second),
            default => 0,
        };

        $validator = Validator::make(
            ['math-captcha' => (string) $result],
            ['math-captcha' => 'math_captcha']
        );

        $this->assertFalse($validator->fails());
    }

    public function test_math_captcha_rule_fails_with_wrong_answer(): void
    {
        app('math-captcha')->label();

        $validator = Validator::make(
            ['math-captcha' => '99999'],
            ['math-captcha' => 'math_captcha']
        );

        $this->assertTrue($validator->fails());
    }

    public function test_math_captcha_rule_fails_with_non_string_value(): void
    {
        app('math-captcha')->label();

        $validator = Validator::make(
            ['math-captcha' => ['array']],
            ['math-captcha' => 'math_captcha']
        );

        $this->assertTrue($validator->fails());
    }
}
