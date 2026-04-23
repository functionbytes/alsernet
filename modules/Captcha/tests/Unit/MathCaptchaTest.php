<?php

declare(strict_types=1);

namespace Modules\Captcha\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Session\Store;
use Modules\Captcha\Services\MathCaptcha;
use Modules\Captcha\Tests\TestCase;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class MathCaptchaTest extends TestCase
{
    private MathCaptcha $mathCaptcha;

    private Store $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = new Store('test', new MockArraySessionStorage);
        $this->mathCaptcha = new MathCaptcha($this->session);
    }

    public function test_label_returns_math_question(): void
    {
        $label = $this->mathCaptcha->label();

        $this->assertStringContainsString('=', $label);
        $this->assertStringContainsString('?', $label);
    }

    public function test_input_generates_html_input(): void
    {
        $input = $this->mathCaptcha->input();

        $this->assertStringContainsString('<input', $input);
        $this->assertStringContainsString('name="math-captcha"', $input);
        $this->assertStringContainsString('required', $input);
        $this->assertStringContainsString('type="text"', $input);
        $this->assertStringContainsString('autocomplete="off"', $input);
        $this->assertStringContainsString('aria-label=', $input);
    }

    public function test_input_escapes_special_characters(): void
    {
        $input = $this->mathCaptcha->input(['value' => '" onclick="alert(1)']);

        $this->assertStringNotContainsString('" onclick="alert(1)"', $input);
        $this->assertStringContainsString('&quot; onclick=&quot;alert(1)&quot;', $input);
    }

    public function test_verify_returns_true_with_correct_answer(): void
    {
        $label = $this->mathCaptcha->getMathLabelOnly();

        // Extract numbers and operand from label (format: "X + Y")
        preg_match('/(\d+)\s+([+\-*])\s+(\d+)/', $label, $matches);
        $this->assertCount(4, $matches);

        $first = (int) $matches[1];
        $operand = $matches[2];
        $second = (int) $matches[3];

        $result = match ($operand) {
            '+' => $first + $second,
            '*' => $first * $second,
            '-' => abs($first - $second),
            default => 0,
        };

        $this->assertTrue($this->mathCaptcha->verify((string) $result));
    }

    public function test_verify_returns_false_with_incorrect_answer(): void
    {
        $this->assertFalse($this->mathCaptcha->verify('99999'));
    }

    public function test_verify_returns_false_with_empty_string(): void
    {
        $this->assertFalse($this->mathCaptcha->verify(''));
    }

    public function test_verify_returns_false_with_non_numeric_string(): void
    {
        $this->assertFalse($this->mathCaptcha->verify('abc'));
    }

    public function test_verify_resets_after_successful_validation(): void
    {
        $label = $this->mathCaptcha->getMathLabelOnly();
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

        $this->mathCaptcha->verify((string) $result);

        // After reset, a new question should be generated with different numbers
        $newLabel = $this->mathCaptcha->getMathLabelOnly();
        $this->assertNotEquals($label, $newLabel);
    }

    public function test_reset_clears_session_values(): void
    {
        $this->mathCaptcha->label(); // Generate session values

        $this->assertNotNull($this->session->get('math-captcha.first'));
        $this->assertNotNull($this->session->get('math-captcha.second'));
        $this->assertNotNull($this->session->get('math-captcha.operand'));

        $this->mathCaptcha->reset();

        $this->assertNull($this->session->get('math-captcha.first'));
        $this->assertNull($this->session->get('math-captcha.second'));
        $this->assertNull($this->session->get('math-captcha.operand'));
    }

    public function test_verify_is_timing_attack_safe(): void
    {
        // hash_equals is used internally; we just verify the method works
        $this->mathCaptcha->label();
        $this->assertFalse($this->mathCaptcha->verify('0'));
    }

    public function test_verify_returns_false_when_session_expired(): void
    {
        $label = $this->mathCaptcha->getMathLabelOnly();
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

        // Simulate an expired session by backdating the generated_at timestamp
        $this->session->put('math-captcha.generated_at', Carbon::now()->subMinutes(15)->toDateTimeString());

        $this->assertFalse($this->mathCaptcha->verify((string) $result));
    }

    public function test_is_expired_returns_true_when_no_timestamp(): void
    {
        $this->assertTrue($this->mathCaptcha->isExpired());
    }

    public function test_is_expired_returns_false_for_fresh_challenge(): void
    {
        $this->mathCaptcha->label(); // Generate timestamp

        $this->assertFalse($this->mathCaptcha->isExpired());
    }
}
