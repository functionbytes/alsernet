<?php

/**
 * League.Csv (https://csv.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Csv;

use const E_USER_WARNING;
use const E_WARNING;

use ErrorException;
use Throwable;

use function in_array;
use function restore_error_handler;
use function set_error_handler;

/**
 * @internal Utility class to wrap callbacks to control emitted warnings during their execution.
 *
 * @template TReturn
 */
final class Warning
{
    /**
     * Converts PHP Warning into ErrorException.
     *
     * @param  mixed  ...$arguments  the callback arguments if needed
     * @return TReturn The result returned by the callback.
     *
     * @throws ErrorException If the callback internally emits a Warning
     * @throws Throwable on callback execution if the callback throws
     */
    public static function trap(callable $callback, mixed ...$arguments): mixed
    {
        set_error_handler(
            fn (int $errno, string $errstr, string $errfile, int $errline): bool => in_array($errno, [E_WARNING, E_USER_WARNING], true)
                ? throw new ErrorException($errstr, 0, $errno, $errfile, $errline)
                : false
        );

        return self::execute($callback, $arguments);
    }

    /**
     * Hides PHP Warnings.
     *
     * @param  mixed  ...$arguments  the callback arguments if needed
     * @return TReturn The result returned by the callback.
     *
     * @throws Throwable on callback execution if the callback throws
     */
    public static function cloak(callable $callback, mixed ...$arguments): mixed
    {
        set_error_handler(
            fn (int $errno, string $errstr, string $errfile, int $errline): bool => in_array($errno, [E_WARNING, E_USER_WARNING], true),
        );

        return self::execute($callback, $arguments);
    }

    /**
     * @param  array<mixed>  $arguments  the callback arguments if needed
     * @return TReturn The result returned by the callback.
     */
    private static function execute(callable $callback, array $arguments)
    {
        try {
            return $callback(...$arguments);
        } finally {
            restore_error_handler();
        }
    }
}
