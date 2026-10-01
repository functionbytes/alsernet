<?php

namespace Modules\HelpdeskChatFlow\Services\Support;

/**
 * Regex evaluation for patterns written by flow designers.
 *
 * Patterns are stored without delimiters and always run as Unicode. Because a
 * pattern is applied to arbitrary customer text, every execution is capped by
 * a low backtrack limit (with the JIT off, so the limit is really enforced)
 * and the subject length is bounded: a catastrophic pattern ends as "no match"
 * instead of freezing a worker. At save time {@see self::problem()} also
 * rejects the usual catastrophic shapes up front.
 */
final class SafeRegex
{
    public const MAX_PATTERN_LENGTH = 200;

    public const MAX_SUBJECT_LENGTH = 500;

    private const BACKTRACK_LIMIT = '20000';

    /** Delimiter that cannot appear in a typed pattern, so no escaping is needed. */
    private const DELIMITER = "\x01";

    /**
     * Subjects that make nested-quantifier patterns blow up quickly.
     *
     * @var array<int, string>
     */
    private const PROBES = [
        'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa!',
        '1111111111111111111111111111111111111111111!',
        'a a a a a a a a a a a a a a a a a a a a a a !',
        'abababababababababababababababababababababab!',
    ];

    /**
     * Why the pattern cannot be used, or null when it is valid and safe.
     */
    public static function problem(string $pattern): ?string
    {
        if (trim($pattern) === '') {
            return 'el patrón está vacío';
        }

        if (mb_strlen($pattern) > self::MAX_PATTERN_LENGTH) {
            return 'el patrón supera los '.self::MAX_PATTERN_LENGTH.' caracteres';
        }

        if (preg_match('/[\x00-\x08\x0B-\x1F]/', $pattern) === 1) {
            return 'el patrón contiene caracteres de control';
        }

        if (self::hasNestedQuantifier($pattern)) {
            return 'el patrón tiene cuantificadores anidados (p. ej. «(a+)+») y podría bloquear el servidor';
        }

        if (self::run($pattern, '') === null) {
            return 'el patrón no es una expresión regular válida';
        }

        foreach (self::PROBES as $probe) {
            if (self::run($pattern, $probe) === null) {
                return 'el patrón es demasiado costoso de evaluar (backtracking excesivo)';
            }
        }

        return null;
    }

    public static function isSafe(string $pattern): bool
    {
        return self::problem($pattern) === null;
    }

    /**
     * Whether the subject matches. Invalid or over-limit patterns never match.
     */
    public static function matches(string $pattern, string $subject): bool
    {
        if ($pattern === '' || mb_strlen($pattern) > self::MAX_PATTERN_LENGTH) {
            return false;
        }

        return self::run($pattern, mb_substr($subject, 0, self::MAX_SUBJECT_LENGTH)) === 1;
    }

    /**
     * Quantified group that itself contains a quantifier, e.g. (a+)+, (a*)*,
     * (\w+\s?)+ or (a|aa)+: the classic catastrophic-backtracking shape.
     */
    private static function hasNestedQuantifier(string $pattern): bool
    {
        $stripped = preg_replace('/\\\\./su', 'x', $pattern) ?? $pattern;

        if (preg_match('/\((?:[^()]*[+*]|[^()]*\{\d+,\d*\})[^()]*\)\s*(?:[+*]|\{\d+,\d*\})/u', $stripped) === 1) {
            return true;
        }

        return preg_match('/\([^()]*\|[^()]*\)\s*[+*]/u', $stripped) === 1;
    }

    /**
     * 1/0 for match/no match, or null when the pattern is invalid or a PCRE
     * limit was hit.
     */
    private static function run(string $pattern, string $subject): ?int
    {
        $previousJit = ini_set('pcre.jit', '0');
        $previousLimit = ini_set('pcre.backtrack_limit', self::BACKTRACK_LIMIT);

        set_error_handler(static fn (): bool => true);

        try {
            $result = preg_match(self::DELIMITER.$pattern.self::DELIMITER.'u', $subject);
            $error = preg_last_error();
        } finally {
            restore_error_handler();

            if ($previousJit !== false) {
                ini_set('pcre.jit', $previousJit);
            }
            if ($previousLimit !== false) {
                ini_set('pcre.backtrack_limit', $previousLimit);
            }
        }

        if ($result === false || $error !== PREG_NO_ERROR) {
            return null;
        }

        return $result;
    }
}
