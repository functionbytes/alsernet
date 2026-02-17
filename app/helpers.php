<?php

/**
 * Apply filters to a value (compatibility function for WordPress-style hooks).
 * For now, this simply returns the value as no hooks are registered.
 * This can be extended in the future to support a proper hook system.
 *
 * @param  string  $hook  The filter name
 * @param  mixed  $value  The value to filter
 * @param  mixed  ...$args  Additional arguments passed to callbacks
 * @return mixed
 */
if (! function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, ...$args): mixed
    {
        return $value;
    }
}
