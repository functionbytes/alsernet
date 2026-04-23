<?php

namespace Modules\Optimize\Http\Middleware;

use Modules\Optimize\Services\ThemeOptimizerService;

/**
 * Inyecta `<link rel="preload">` para los CSS críticos declarados en
 * platform/themes/{theme}/config.php['assets']['css'] y la primera imagen
 * hero detectada en el HTML. Esto anticipa la descarga de recursos críticos
 * → mejora LCP en PageSpeed.
 *
 * Lee el tema activo desde setting('template') y precarga los CSS prioritarios:
 * style, main, custom, bootstrap (en ese orden de importancia).
 */
class InjectCriticalPreload extends PageSpeed
{
    private static ?array $criticalCss = null;

    public function apply(string $buffer): string
    {
        $preloads = [];

        // Precargar CSS críticos del tema activo
        foreach ($this->getCriticalCss() as $href) {
            if (! $this->hasPreload($buffer, $href)) {
                $preloads[] = '<link rel="preload" as="style" href="'.htmlspecialchars($href, ENT_QUOTES).'">';
            }
        }

        // Primera imagen con class hero|banner|slider → preload LCP candidate.
        if (preg_match('/<img[^>]+class=["\'][^"\']*(?:hero|banner|slider)[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $buffer, $m)
            || preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]+class=["\'][^"\']*(?:hero|banner|slider)[^"\']*["\'][^>]*>/i', $buffer, $m)) {
            $src = $m[1];
            if (! $this->hasPreload($buffer, $src)) {
                $preloads[] = '<link rel="preload" as="image" href="'.htmlspecialchars($src, ENT_QUOTES).'" fetchpriority="high">';
            }
        }

        if (empty($preloads)) {
            return $buffer;
        }

        $injection = implode("\n", $preloads)."\n";

        // Insertar justo después de <head> para que resuelvan lo antes posible.
        return preg_replace('/<head(\b[^>]*)>/i', '<head$1>'.$injection, $buffer, 1) ?? $buffer;
    }

    /**
     * @return array<int, string>
     */
    private function getCriticalCss(): array
    {
        if (self::$criticalCss !== null) {
            return self::$criticalCss;
        }

        try {
            $optimizer = app(ThemeOptimizerService::class);
            self::$criticalCss = $optimizer->getCriticalCss();
        } catch (\Throwable $e) {
            self::$criticalCss = [];
        }

        return self::$criticalCss;
    }

    private function hasPreload(string $buffer, string $href): bool
    {
        return str_contains($buffer, 'rel="preload"'.'" href="'.$href)
            || preg_match('/<link[^>]+rel=["\']preload["\'][^>]+href=["\']'.preg_quote($href, '/').'["\']/i', $buffer);
    }
}
