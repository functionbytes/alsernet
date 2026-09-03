<?php

namespace Modules\HelpdeskEmailActivity\Support;

use Illuminate\Support\Facades\File;

/**
 * Acceso al dataset de compatibilidad de caniemail.com que alimenta la pestaña
 * "Compatibilidad" del inspector de mensajes.
 *
 * El JSON (~640 KB, 300+ features) vive EN EL REPO —
 * resources/data/caniemail.json— y no se descarga en caliente: la comprobación
 * de un correo no puede depender de que caniemail.com esté disponible ni meter
 * una petición saliente en mitad de una vista del panel. Para refrescarlo está
 * UpdateCanIEmailDataCommand.
 *
 * El parseo se cachea en una propiedad estática (no en Cache::): son ~5 MB de
 * arrays PHP, demasiado para serializar en Redis en cada petición, y dentro de
 * una misma petición solo se paga una vez aunque se evalúen 190 tests.
 */
class CanIEmailDataset
{
    /** @var array<string, mixed>|null */
    private static ?array $data = null;

    /** @var array<string, array<string, mixed>>|null Índice slug => feature */
    private static ?array $bySlug = null;

    public static function path(): string
    {
        return module_path('HelpdeskEmailActivity', 'resources/data/caniemail.json');
    }

    /**
     * Vacía la caché estática — para los tests, que sustituyen el fichero.
     */
    public static function flush(): void
    {
        self::$data = null;
        self::$bySlug = null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        $path = self::path();

        if (! File::exists($path)) {
            return self::$data = ['data' => [], 'nicenames' => ['family' => [], 'platform' => []]];
        }

        $decoded = json_decode(File::get($path), true);

        return self::$data = is_array($decoded)
            ? $decoded
            : ['data' => [], 'nicenames' => ['family' => [], 'platform' => []]];
    }

    /**
     * Feature completa por slug (p. ej. "css-margin"), o null si el dataset no
     * la conoce — puede pasar tras una actualización que retire una feature,
     * y en ese caso el test simplemente no genera aviso.
     *
     * @return array<string, mixed>|null
     */
    public static function feature(string $slug): ?array
    {
        if (self::$bySlug === null) {
            self::$bySlug = [];

            foreach (self::all()['data'] ?? [] as $feature) {
                if (isset($feature['slug'])) {
                    self::$bySlug[$feature['slug']] = $feature;
                }
            }
        }

        return self::$bySlug[$slug] ?? null;
    }

    public static function familyName(string $family): string
    {
        return self::all()['nicenames']['family'][$family] ?? $family;
    }

    public static function platformName(string $platform): string
    {
        return self::all()['nicenames']['platform'][$platform] ?? $platform;
    }

    public static function lastUpdate(): ?string
    {
        return self::all()['last_update_date'] ?? null;
    }

    /**
     * Plataformas presentes en el dataset con los clientes de cada una, para
     * los conmutadores "Plataformas evaluadas" del panel.
     *
     * @return array<string, list<string>>
     */
    public static function platforms(): array
    {
        $platforms = [];

        foreach (self::all()['data'] ?? [] as $feature) {
            foreach ($feature['stats'] ?? [] as $family => $byPlatform) {
                $niceFamily = self::familyName($family);

                foreach (array_keys($byPlatform) as $platform) {
                    $platforms[$platform] ??= [];

                    if (! in_array($niceFamily, $platforms[$platform], true)) {
                        $platforms[$platform][] = $niceFamily;
                    }
                }
            }
        }

        foreach ($platforms as $platform => $families) {
            sort($families);
            $platforms[$platform] = $families;
        }

        ksort($platforms);

        return $platforms;
    }
}
