<?php

namespace Modules\Shortcode\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Persistencia simple de estadísticas de uso por shortcode vía Cache store.
 *
 * Diseño: counters en un único hash de array para evitar race conditions
 * de múltiples claves. TTL largo (30 días); clearCache no los invalida.
 */
class ShortcodeUsageStats
{
    protected const CACHE_KEY = 'shortcode:usage:counts';

    protected const CACHE_TTL = 2592000; // 30 días

    /**
     * Incrementa el contador de un nombre.
     */
    public function increment(string $name): void
    {
        $counts = Cache::get(self::CACHE_KEY, []);
        $counts[$name] = ($counts[$name] ?? 0) + 1;
        Cache::put(self::CACHE_KEY, $counts, self::CACHE_TTL);
    }

    /**
     * Incrementa varios nombres en una sola escritura.
     *
     * @param  array<int, string>  $names
     */
    public function incrementMany(array $names): void
    {
        if (empty($names)) {
            return;
        }

        $counts = Cache::get(self::CACHE_KEY, []);
        foreach ($names as $name) {
            $counts[$name] = ($counts[$name] ?? 0) + 1;
        }
        Cache::put(self::CACHE_KEY, $counts, self::CACHE_TTL);
    }

    /**
     * Retorna todos los contadores ordenados descendentemente.
     *
     * @return array<string, int>
     */
    public function all(): array
    {
        $counts = Cache::get(self::CACHE_KEY, []);
        arsort($counts);

        return $counts;
    }

    /**
     * Top N shortcodes por uso.
     *
     * @return array<string, int>
     */
    public function top(int $limit = 10): array
    {
        return array_slice($this->all(), 0, $limit, true);
    }

    public function reset(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
