<?php

namespace Modules\Mailer\Services;

use Modules\Mailer\Models\MailerVariable;

class MailerVariableValueService
{
    /**
     * Obtener un array con todos los valores reales de variables traducidas para un idioma específico
     *
     * OPTIMIZACIÓN: Usa caché de Laravel (1 hora) + caché estático
     * El caché se limpia automáticamente cuando se modifican variables (ver MailerVariable model observer)
     *
     * @param  int  $langId  ID del idioma
     * @param  string|null  $module  Módulo específico (optional, si no se proporciona trae todas)
     * @return array Array con key => value traducido
     */
    public static function getTranslatedValues(int $langId, ?string $module = null): array
    {
        // Caché estático (en memoria) para evitar consultas repetidas en la misma request
        static $staticCache = [];

        $cacheKey = "mailer_vars_{$langId}_".($module ?? 'all');

        // 1. Revisar caché estático primero (más rápido)
        if (isset($staticCache[$cacheKey])) {
            return $staticCache[$cacheKey];
        }

        // 2. Revisar caché de Laravel (Redis/File) - se limpia automáticamente al guardar/editar
        $result = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($langId, $module) {
            $query = MailerVariable::query()
                ->where('is_enabled', true)
                ->with(['translations' => function ($q) use ($langId) {
                    $q->where('lang_id', $langId);
                }]);

            if ($module) {
                $query->where(function ($q) use ($module) {
                    $q->where('module', $module)
                        ->orWhere('module', 'core');
                });
            }

            $variables = $query->get();
            $result = [];

            foreach ($variables as $variable) {
                $translation = $variable->translations->first();
                $value = $translation?->value ?? $variable->value ?? '';

                // Usar el valor traducido si existe, si no, usar el valor de la variable principal o vacío
                // Retornar AMBAS versiones: minúsculas Y MAYÚSCULAS para máxima compatibilidad
                $result[$variable->key] = $value;
                $result[strtoupper($variable->key)] = $value;
            }

            return $result;
        });

        // 3. Guardar en caché estático para esta request
        $staticCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Limpiar TODO el caché de variables traducidas
     * Se llama automáticamente desde el observer del modelo cuando se guardan/actualizan variables
     */
    public static function clearCache(): void
    {
        // Limpiar caché de Laravel para todas las combinaciones posibles
        // Patrón: mailer_vars_*
        \Illuminate\Support\Facades\Cache::flush(); // Si usas tags, puedes ser más específico
    }

    /**
     * Obtener todas las variables como array con su información completa
     */
    public static function getVariablesWithInfo(int $langId, ?string $module = null): array
    {
        $query = MailerVariable::query()
            ->where('is_enabled', true)
            ->with(['translations' => function ($q) use ($langId) {
                $q->where('lang_id', $langId);
            }]);

        if ($module) {
            $query->where(function ($q) use ($module) {
                $q->where('module', $module)
                    ->orWhere('module', 'core');
            });
        }

        $variables = $query->get();
        $result = [];

        foreach ($variables as $variable) {
            $translation = $variable->translations->first();

            $result[$variable->key] = [
                'key' => $variable->key,
                'name' => $translation?->name ?? $variable->name,
                'description' => $translation?->description ?? $variable->description,
                'value' => $translation?->value ?? $variable->value ?? '',
                'example_value' => $variable->example_value,
                'category' => $variable->category,
                'module' => $variable->module,
            ];
        }

        return $result;
    }

    /**
     * Obtener valor de una variable específica traducida
     *
     * @param  string  $key  Clave de la variable (ej: COMPANY_NAME)
     * @param  int  $langId  ID del idioma
     */
    public static function getValue(string $key, int $langId): ?string
    {
        $variable = MailerVariable::where('key', $key)
            ->where('is_enabled', true)
            ->with(['translations' => function ($q) use ($langId) {
                $q->where('lang_id', $langId);
            }])
            ->first();

        if (! $variable) {
            return null;
        }

        $translation = $variable->translations->first();

        return $translation?->value ?? $variable->value ?? '';
    }

    /**
     * Obtener valor de múltiples variables a la vez
     *
     * @param  array  $keys  Array de claves (ej: ['COMPANY_NAME', 'SITE_NAME'])
     * @param  int  $langId  ID del idioma
     */
    public static function getValues(array $keys, int $langId): array
    {
        $variables = MailerVariable::whereIn('key', $keys)
            ->where('is_enabled', true)
            ->with(['translations' => function ($q) use ($langId) {
                $q->where('lang_id', $langId);
            }])
            ->get();

        $result = [];

        foreach ($variables as $variable) {
            $translation = $variable->translations->first();
            $result[$variable->key] = $translation?->value ?? $variable->value ?? '';
        }

        return $result;
    }
}
