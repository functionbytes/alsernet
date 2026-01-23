<?php

/**
 * Database Query Helpers
 *
 * Provides utility functions for database operations, table prefixing,
 * and SQL escaping.
 */
if (! function_exists('table')) {
    /**
     * Get the prefixed table name
     *
     * @param  string  $name  The table name
     * @return string The prefixed table name
     */
    function table($name)
    {
        return \DB::getTablePrefix().$name;
    }
}

if (! function_exists('quote')) {
    /**
     * Wrap a value in single quotes for SQL
     *
     * @param  string  $value  The value to quote
     * @return string The quoted value
     */
    function quote($value)
    {
        return "'$value'";
    }
}

if (! function_exists('db_quote')) {
    /**
     * Quote a value using the database PDO connection
     *
     * @param  string  $value  The value to quote
     * @return string The properly quoted value
     */
    function db_quote($value)
    {
        return \DB::connection()->getPdo()->quote($value);
    }
}
