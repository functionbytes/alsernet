<?php

namespace Modules\Media\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Media\Models\FolderTemplate;

class FolderTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Proyecto cliente',
                'description' => 'Estructura base para proyectos de clientes',
                'structure' => [
                    ['name' => 'Docs', 'color' => '#3498db', 'children' => []],
                    ['name' => 'Imagenes', 'color' => '#2ecc71', 'children' => []],
                    ['name' => 'Videos', 'color' => '#e74c3c', 'children' => []],
                    ['name' => 'Entregables', 'color' => '#f39c12', 'children' => []],
                ],
                'is_shared' => true,
            ],
            [
                'name' => 'Producto e-commerce',
                'description' => 'Estructura para catalogos de productos en tiendas online',
                'structure' => [
                    ['name' => 'Fotos', 'color' => '#9b59b6', 'children' => [
                        ['name' => 'Portada', 'color' => '#9b59b6', 'children' => []],
                        ['name' => 'Variaciones', 'color' => '#9b59b6', 'children' => []],
                    ]],
                    ['name' => 'Videos', 'color' => '#e74c3c', 'children' => []],
                    ['name' => 'Manuales', 'color' => '#1abc9c', 'children' => []],
                ],
                'is_shared' => true,
            ],
            [
                'name' => 'Evento corporativo',
                'description' => 'Estructura para la gestion de medios de eventos corporativos',
                'structure' => [
                    ['name' => 'Fotos', 'color' => '#2ecc71', 'children' => []],
                    ['name' => 'Videos', 'color' => '#e74c3c', 'children' => []],
                    ['name' => 'Presentaciones', 'color' => '#3498db', 'children' => []],
                    ['name' => 'Audios', 'color' => '#f39c12', 'children' => []],
                ],
                'is_shared' => true,
            ],
        ];

        foreach ($templates as $data) {
            FolderTemplate::firstOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'slug' => Str::slug($data['name']),
                    'description' => $data['description'],
                    'structure' => $data['structure'],
                    'user_id' => null,
                    'is_shared' => $data['is_shared'],
                ]
            );
        }
    }
}
