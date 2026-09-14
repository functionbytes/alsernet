<?php

namespace Modules\Helpdesk\Services;

class PiiMaskingService
{
    /**
     * Detect and mask PII patterns in a text string.
     *
     * Patterns handled:
     *   - Credit cards (13–19 digits, optionally space/dash separated) → **** **** **** 1234
     *   - Spanish DNI (8 digits + letter)                              → *****678X
     *
     *   - Email addresses                                              → j***@domain.com
     *   - Phone numbers (9–15 consecutive digits)                      → ***1234
     */
    public function mask(string $text): string
    {
        $text = $this->maskCreditCards($text);
        $text = $this->maskDni($text);
        $text = $this->maskEmails($text);
        $text = $this->maskPhones($text);

        return $text;
    }

    private function maskCreditCards(string $text): string
    {
        // Matches 13–19 digit card numbers with optional spaces/dashes between groups
        return preg_replace_callback(
            '/\b(\d[\d\s\-]{11,17}\d)\b/',
            function ($m) {
                $digits = preg_replace('/\D/', '', $m[0]);
                if (strlen($digits) < 13 || strlen($digits) > 19) {
                    return $m[0];
                }
                $last4 = substr($digits, -4);

                return '**** **** **** '.$last4;
            },
            $text
        );
    }

    private function maskDni(string $text): string
    {
        // Spanish DNI: 8 digits followed by a letter (e.g. 12345678X)
        return preg_replace_callback(
            '/\b(\d{8})([A-Za-z])\b/',
            fn ($m) => '*****'.substr($m[1], -3).$m[2],
            $text
        );
    }

    private function maskEmails(string $text): string
    {
        return preg_replace_callback(
            '/\b([a-zA-Z0-9._%+\-])([a-zA-Z0-9._%+\-]*)(@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})\b/',
            fn ($m) => $m[1].'***'.$m[3],
            $text
        );
    }

    private function maskPhones(string $text): string
    {
        // 9–15 consecutive digits not already matched as credit card
        return preg_replace_callback(
            '/(?<!\d)(\d{9,15})(?!\d)/',
            function ($m) {
                $digits = $m[1];
                $len = strlen($digits);

                if ($len <= 4) {
                    return $digits;
                }

                return str_repeat('*', $len - 4).substr($digits, -4);
            },
            $text
        );
    }
}
