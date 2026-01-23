<?php

/**
 * Localization Helpers
 *
 * Provides functions for formatting numbers and accessing localization configuration.
 */
if (! function_exists('number_with_delimiter')) {
    /**
     * Format a number with locale-specific delimiters
     *
     * @param  mixed  $number  The number to format
     * @param  int|null  $precision  Number of decimal places
     * @param  string|null  $seperator  Thousands separator
     * @param  string|null  $locale  The locale to use (default: 'es')
     * @return string|mixed The formatted number
     */
    function number_with_delimiter($number, $precision = null, $seperator = null, $locale = null)
    {
        if (! is_numeric($number)) {
            return $number;
        }

        if (is_null($locale)) {
            $locale = 'es';
        }

        if (floor($number) == $number && is_null($precision)) {
            $precision = 0;
        }

        if (is_null($precision)) {
            $precision = get_localization_config('number_precision', $locale);
        }

        $decimal = get_localization_config('number_decimal_separator', $locale);

        if (is_null($seperator)) {
            $seperator = get_localization_config('number_thousands_separator', $locale);
        }

        return number_format($number, $precision, $decimal, $seperator);
    }
}

if (! function_exists('get_localization_config')) {
    /**
     * Get a localization configuration value
     *
     * @param  string  $name  The configuration key
     * @param  string  $locale  The locale code
     * @return mixed The configuration value
     *
     * @throws \Exception
     */
    function get_localization_config($name, $locale)
    {
        $defaultConfig = config('localization')['*'];

        if (array_key_exists($locale, config('localization'))) {
            $config = config('localization')[$locale];
        }

        if (isset($config) && array_key_exists($name, $config) && array_key_exists($name, $defaultConfig)) {
            return $config[$name];
        } elseif (array_key_exists($name, $defaultConfig)) {
            return $defaultConfig[$name];
        } else {
            throw new \Exception('Localization config for "'.$name.'" does not exist');
        }
    }
}
