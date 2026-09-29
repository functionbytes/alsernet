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
        // 29-sep-2026: la web ya NO reescribe el .env (perdía comentarios,
        // permitía inyectar claves con saltos de línea y exigía un .env
        // escribible por www-data). Los ajustes van a la tabla settings y se
        // aplican en runtime (ver SystemServiceProvider::applyRuntimeSettings).
        \Illuminate\Support\Facades\Log::warning('write_env() ignorado: el .env no se modifica desde la aplicación', ['key' => (string) $key]);

        return false;
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
