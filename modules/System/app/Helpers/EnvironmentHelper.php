<?php

/**
 * Environment and Configuration File Helpers
 *
 * Provides functions for reading and writing environment variables
 * in the .env file.
 */
if (! function_exists('write_env')) {
    /**
     * Write an environment variable to the .env file
     *
     * Clears the config cache to ensure the new value is loaded.
     *
     * @param  string  $key  The environment variable key
     * @param  string  $value  The environment variable value
     * @param  bool  $overwrite  Whether to overwrite existing values
     * @return void
     */
    function write_env($key, $value, $overwrite = true)
    {
        \Artisan::call('config:clear');

        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            unlink(base_path('bootstrap/cache/config.php'));
        }

        $envs = load_env_from_file(app()->environmentFilePath());

        if ($overwrite || ! array_key_exists($key, $envs) || empty($envs[$key])) {
            if (preg_match('/[\s\#!\$]/', $value)) {
                $value = addcslashes($value, '"');
                $value = "\"$value\"";
            }

            $envs[$key] = $value;
        } else {
            return;
        }

        $out = [];
        foreach ($envs as $k => $v) {
            $out[] = "$k=$v";
        }

        $out = implode("\n", $out);

        file_put_contents(app()->environmentFilePath(), $out);
    }
}

if (! function_exists('load_env_from_file')) {
    /**
     * Load environment variables from the .env file
     *
     * Parses the .env file and returns a key-value array.
     *
     * @param  string  $path  Path to the .env file
     * @return array Array of environment variables
     */
    function load_env_from_file($path)
    {
        $content = file_get_contents($path);
        $lines = preg_split("/(\r\n|\n|\r)/", $content);
        $lines = array_where($lines, function ($value, $key) {
            if (is_null($value)) {
                return false;
            }

            if (preg_match('/^[a-zA-Z0-9_]+=/', $value)) {
                return true;
            } else {
                return false;
            }
        });

        $output = [];
        foreach ($lines as $line) {
            [$key, $value] = explode('=', $line, 2);

            if (is_null($value)) {
                $value = '';
            } else {
                $value = trim($value);
            }

            $output[$key] = $value;
        }

        return $output;
    }
}
