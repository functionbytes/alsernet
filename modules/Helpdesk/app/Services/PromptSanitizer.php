<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Log;

class PromptSanitizer
{
    private const MAX_LENGTH = 10000;

    private const LOG_PREVIEW_LENGTH = 200;

    /**
     * Sanitize user input before sending to an LLM provider.
     *
     * - Truncates to MAX_LENGTH characters
     * - Strips control characters (except \n, \t, \r)
     * - Replaces prompt injection patterns with [FILTERED]
     * - Logs suspicious inputs to the security channel
     */
    public function sanitize(string $input, ?int $userId = null): string
    {
        $input = $this->truncate($input);
        $input = $this->stripControlCharacters($input);
        $input = $this->filterInjectionPatterns($input, $userId);

        return $input;
    }

    private function truncate(string $input): string
    {
        if (mb_strlen($input) <= self::MAX_LENGTH) {
            return $input;
        }

        return mb_substr($input, 0, self::MAX_LENGTH);
    }

    private function stripControlCharacters(string $input): string
    {
        // Remove all control chars < 0x20 except TAB (0x09), LF (0x0A), CR (0x0D)
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input) ?? $input;
    }

    private function filterInjectionPatterns(string $input, ?int $userId): string
    {
        $patterns = config('helpdesk.prompt_injection_patterns', []);

        foreach ($patterns as $pattern) {
            if (@preg_match($pattern, $input) === 1) {
                Log::channel($this->resolveSecurityChannel())->warning('Prompt injection attempt detected', [
                    'user_id' => $userId,
                    'pattern' => $pattern,
                    'input_preview' => mb_substr($input, 0, self::LOG_PREVIEW_LENGTH),
                ]);

                $input = preg_replace($pattern, '[FILTERED]', $input) ?? $input;
            }
        }

        return $input;
    }

    private function resolveSecurityChannel(): string
    {
        $channels = config('logging.channels', []);

        return isset($channels['security']) ? 'security' : config('logging.default', 'stack');
    }
}
