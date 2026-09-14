<?php

namespace Modules\Media\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Media\Models\MediaFolder;

/**
 * Estructura inicial de carpetas del gestor de medios.
 *
 * Cuatro raices (Documentos, Imagenes, Videos, Archivos) con sus subcarpetas.
 *
 * OJO con el historial de este archivo: las definiciones traian columnas que
 * media_folders nunca ha tenido (key, description, path, icon, position,
 * is_protected, is_active) y el firstOrCreate buscaba por 'key', asi que el
 * seeder reventaba con "Unknown column 'key'" y la tabla se quedaba a cero.
 * Las columnas reales son: uid, name, slug, parent_id, user_id, color, disk.
 * El uid y el slug los rellena solo el modelo en su hook creating().
 *
 * La jerarquia tampoco llego a funcionar nunca: todas las carpetas se creaban
 * con parent_id null y un comentario "will be set after creation" que no
 * ejecutaba nadie. Aqui los hijos se cuelgan de su raiz de verdad.
 */
class MediaFolderSeeder extends Seeder
{
    public function run(): void
    {
        $created = 0;

        foreach ($this->tree() as $root) {
            $parent = $this->folder($root['slug'], $root['name'], $root['color'], null);
            $created++;

            foreach ($root['children'] as $child) {
                $this->folder($child['slug'], $child['name'], $child['color'], $parent->id);
                $created++;
            }
        }

        $this->command?->info("Sembradas {$created} carpetas de medios.");
    }

    /**
     * El slug es la clave: re-sembrar no duplica.
     */
    private function folder(string $slug, string $name, string $color, ?int $parentId): MediaFolder
    {
        return MediaFolder::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'color' => $color,
                'parent_id' => $parentId,
            ],
        );
    }

    /**
     * @return array<int, array{slug: string, name: string, color: string, children: array<int, array{slug: string, name: string, color: string}>}>
     */
    private function tree(): array
    {
        return [
            [
                'slug' => 'documentos',
                'name' => 'Documentos',
                'color' => '#0d6efd',
                'children' => [
                    ['slug' => 'documentos-contratos', 'name' => 'Contratos', 'color' => '#198754'],
                    ['slug' => 'documentos-facturas', 'name' => 'Facturas', 'color' => '#0dcaf0'],
                    ['slug' => 'documentos-certificados', 'name' => 'Certificados', 'color' => '#ffc107'],
                ],
            ],
            [
                'slug' => 'imagenes',
                'name' => 'Imágenes',
                'color' => '#fd7e14',
                'children' => [
                    ['slug' => 'imagenes-productos', 'name' => 'Productos', 'color' => '#198754'],
                    // Ambar y no el #dc3545 original: en esta UI no se usan rojos.
                    ['slug' => 'imagenes-marketing', 'name' => 'Marketing', 'color' => '#F5B754'],
                    ['slug' => 'imagenes-equipo', 'name' => 'Equipo', 'color' => '#0dcaf0'],
                ],
            ],
            [
                'slug' => 'videos',
                'name' => 'Vídeos',
                'color' => '#6f42c1',
                'children' => [
                    ['slug' => 'videos-tutoriales', 'name' => 'Tutoriales', 'color' => '#198754'],
                    ['slug' => 'videos-promociones', 'name' => 'Promociones', 'color' => '#fd7e14'],
                ],
            ],
            [
                'slug' => 'archivos',
                'name' => 'Archivos',
                'color' => '#6c757d',
                'children' => [
                    ['slug' => 'archivos-procesados', 'name' => 'Procesados', 'color' => '#198754'],
                    ['slug' => 'archivos-historico', 'name' => 'Histórico', 'color' => '#6c757d'],
                ],
            ],
        ];
    }
}
