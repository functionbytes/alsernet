<?php

namespace App\Helpers;

class PiiMasker
{
    public static function email(?string $email): string
    {
        if (! $email) {
            return '';
        }

        if (! str_contains($email, '@')) {
            return '***';
        }

        [$user, $domain] = explode('@', $email, 2);

        $userMasked = strlen($user) > 2
            ? substr($user, 0, 2).str_repeat('*', max(1, strlen($user) - 2))
            : '**';

        return $userMasked.'@'.$domain;
    }

    public static function phone(?string $phone): string
    {
        if (! $phone) {
            return '';
        }

        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', strlen($digits) - 4).substr($digits, -4);
    }
}
