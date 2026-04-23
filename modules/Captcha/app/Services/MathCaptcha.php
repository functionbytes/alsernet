<?php

declare(strict_types=1);

namespace Modules\Captcha\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store;
use Illuminate\Support\HtmlString;
use Modules\Captcha\Events\CaptchaRendered;
use Modules\Captcha\Events\CaptchaRendering;

class MathCaptcha
{
    /**
     * Session lifetime for a math captcha challenge (minutes).
     */
    protected int $sessionTimeout = 10;

    public function __construct(protected SessionManager|Store|null $session = null) {}

    /**
     * Returns the math question as string. The second operand is always a larger
     * number then the first one. So it's on first position because we don't want
     * any negative results.
     */
    public function label(): string
    {
        $label = $this->getMathLabelOnly();

        return __('Please solve the following math function: :label = ?', compact('label'));
    }

    /**
     * Get the math expression without the surrounding text.
     */
    public function getMathLabelOnly(): string
    {
        return sprintf(
            '%d %s %d',
            $this->getMathSecondOperator(),
            $this->getMathOperand(),
            $this->getMathFirstOperator()
        );
    }

    /**
     * Render the HTML input element for the math captcha.
     */
    public function input(array $attributes = []): string
    {
        $default = [];
        $default['type'] = 'text';
        $default['id'] = 'math-captcha';
        $default['name'] = 'math-captcha';
        $default['required'] = true;
        $default['autocomplete'] = 'off';
        $default['aria-label'] = __('Math security verification');
        $default['value'] = old('math-captcha');

        $attributes = array_merge($default, $attributes);

        CaptchaRendering::dispatch($attributes, [], '', '');

        $attributesString = collect($attributes)
            ->map(fn ($value, $key) => sprintf('%s="%s"', $key, htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')))
            ->implode(' ');

        return tap(
            new HtmlString('<input '.$attributesString.'>'),
            fn (HtmlString $rendered) => CaptchaRendered::dispatch($rendered->toHtml(), 'math'),
        );
    }

    /**
     * Verify the user's answer against the session-stored result.
     * Returns false if the challenge has expired.
     */
    public function verify(string $value): bool
    {
        if (empty($value) || ! ctype_digit(ltrim($value, '-'))) {
            return false;
        }

        if ($this->isExpired()) {
            $this->reset();

            return false;
        }

        $expected = (string) $this->getMathResult();

        // Use hash_equals to prevent timing attacks on the comparison.
        $valid = hash_equals($expected, $value);

        if ($valid) {
            // Reset the captcha so the same session answer cannot be replayed.
            $this->reset();
        }

        return $valid;
    }

    /**
     * Check whether the current challenge has expired.
     */
    public function isExpired(): bool
    {
        $generatedAt = $this->session->get('math-captcha.generated_at');

        if (! $generatedAt) {
            return true;
        }

        return now()->diffInMinutes(Carbon::parse($generatedAt)) > $this->sessionTimeout;
    }

    /**
     * Reset the math operators to regenerate a new question.
     */
    public function reset(): void
    {
        $this->session->forget('math-captcha.first');
        $this->session->forget('math-captcha.second');
        $this->session->forget('math-captcha.operand');
        $this->session->forget('math-captcha.generated_at');
    }

    /**
     * Operand to be used ('*','-','+')
     */
    protected function getMathOperand(): string
    {
        if (! $this->session->get('math-captcha.operand')) {
            $operands = config('captcha.general.math-captcha.operands');
            $keys = array_keys($operands);
            $this->session->put(
                'math-captcha.operand',
                config(
                    'captcha.general.math-captcha.operands.'.$keys[random_int(0, count($keys) - 1)]
                )
            );
        }

        return $this->session->get('math-captcha.operand');
    }

    /**
     * The first math operand.
     */
    protected function getMathFirstOperator(): int
    {
        if (! $this->session->get('math-captcha.first')) {
            $this->session->put(
                'math-captcha.first',
                random_int(
                    config('captcha.general.math-captcha.rand-min'),
                    config('captcha.general.math-captcha.rand-max')
                )
            );
        }

        return $this->session->get('math-captcha.first');
    }

    /**
     * The second math operand.
     */
    protected function getMathSecondOperator(): int
    {
        if (! $this->session->get('math-captcha.second')) {
            $this->session->put(
                'math-captcha.second',
                $this->getMathFirstOperator() + random_int(
                    config('captcha.general.math-captcha.rand-min'),
                    config('captcha.general.math-captcha.rand-max')
                )
            );
        }

        return $this->session->get('math-captcha.second');
    }

    /**
     * Calculate the expected result of the current math expression.
     */
    protected function getMathResult(): float|int
    {
        return match ($this->getMathOperand()) {
            '+' => $this->getMathFirstOperator() + $this->getMathSecondOperator(),
            '*' => $this->getMathFirstOperator() * $this->getMathSecondOperator(),
            '-' => abs($this->getMathFirstOperator() - $this->getMathSecondOperator()),
            default => throw new Exception('Math captcha uses an unknown operand.'),
        };
    }
}
