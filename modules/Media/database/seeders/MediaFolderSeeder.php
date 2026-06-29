<?php

namespace Modules\Media\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Core\Models\Setting;

class MediaFolderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the media_folders table with predefined folder structure for organizing media files.
     * Creates a hierarchical organization system for documents, images, and other media assets.
     *
     * Folder structure:
     * - Document: Organized by type (PDFs, contracts, etc.)
     * - Images: Organized by category (products, team, marketing, etc.)
     * - Videos: Organized by purpose (tutorials, promotions, etc.)
     * - Archives: Temporary storage for processed/archived files
     *
     * Depends on: None (independent)
     */
    public function run(): void
    {
        $folders = [
            // Root Document
            [
                'uid' => Str::ulid(),
                'name' => 'Documentos',
                'key' => 'documents',
                'description' => 'Almacenamiento principal de documentos',
                'path' => '/documents',
                'parent_id' => null,
                'icon' => 'fa-duotone fa-folder-open',
                'color' => '#0d6efd',
                'position' => 1,
                'is_protected' => true,
                'is_active' => true,
            ],
            // Document Subfolders
            [
                'uid' => Str::ulid(),
                'name' => 'Contratos',
                'key' => 'documents_contracts',
                'description' => 'Documentos de contratos y acuerdos',
                'path' => '/documents/contracts',
                'parent_id' => null, // Will be set to documents folder after creation
                'icon' => 'fa-duotone fa-file-contract',
                'color' => '#198754',
                'position' => 1,
                'is_protected' => false,
                'is_active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Facturas',
                'key' => 'documents_invoices',
                'description' => 'Facturas y recibos',
                'path' => '/documents/invoices',
                'parent_id' => null,
                'icon' => 'fa-duotone fa-file-invoice',
                'color' => '#0dcaf0',
                'position' => 2,
                'is_protected' => false,
                'is_active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Certificados',
                'key' => 'documents_certificates',
                'description' => 'Certificados y licencias',
                'path' => '/documents/certificates',
                'parent_id' => null,
                'icon' => 'fa-duotone fa-certificate',
                'color' => '#ffc107',
                'position' => 3,
                'is_protected' => false,
                'is_active' => true,
            ],

            // Root Images
            [
                'uid' => Str::ulid(),
                'name' => 'Imágenes',
                'key' => 'images',
                'description' => 'Almacenamiento de imágenes y gráficos',
                'path' => '/images',
                'parent_id' => null,
                'icon' => 'fa-duotone fa-images',
                'color' => '#fd7e14',
                'position' => 2,
                'is_protected' => true,
                'is_active' => true,
            ],
            // Images Subfolders
            [
                'uid' => Str::ulid(),
                'name' => 'Productos',
                'key' => 'images_products',
                'description' => 'Imágenes de productos',
                'path' => '/images/products',
                'parent_id' => null,
                'icon' => 'fa-duotone fa-box-open',
                'color' => '#198754',
                'position' => 1,
                'is_protected' => false,
                'is_active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Marketing',
                'key' => 'images_marketing',
                'description' => 'Materiales de marketing y promoción',
                'path' => '/images/marketing',
                'parent_id' => null,
                'icon' => 'fa-duotone fa-bullhorn',
                'color' => '#dc3545',
                'position' => 2,
                'is_protected' => false,
                'is_active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Equipo',
                'key' => 'images_team',
                'description' => 'Fotos del equipo y personal',
                'path' => '/images/team',
                'parent_id' => null,
                'icon' => 'fa-duotone fa-users',
                'color' => '#0dcaf0',
                'position' => 3,
                'is_protected' => false,
                'is_active' => true,
            ],

            // Root Videos
            [
                'uid' => Str::ulid(),
                'name' => 'Vídeos',
                'key' => 'videos',
                'description' => 'Almacenamiento de vídeos',
                'path' => '/videos',
                'parent_id' => null,
                'icon' => 'fa-duotone fa-video',
                'color' => '#6f42c1',
                'position' => 3,
                'is_protected' => true,
                'is_active' => true,
            ],
            // Videos Subfolders
            [
                'uid' => Str::ulid(),
                'name' => 'Tutoriales',
                'key' => 'videos_tutorials',
                'description' => 'Vídeos de tutoriales y guías',
                'path' => '/videos/tutorials',
                'parent_id' => null,
                'icon' => 'fa-duotone fa-graduation-cap',
                'color' => '#198754',
                'position' => 1,
                'is_protected' => false,
                'is_active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Promociones',
                'key' => 'videos_promotions',
                'description' => 'Vídeos de promoción y publicidad',
                'path' => '/videos/promotions',
                'parent_id' => null,
                'icon' => 'fa-duotone fa-film',
                'color' => '#fd7e14',
                'position' => 2,
                'is_protected' => false,
                'is_active' => true,
            ],

            // Root Archives
            [
                'uid' => Str::ulid(),
                'name' => 'Archivos',
                'key' => 'archives',
                'description' => 'Almacenamiento de archivos procesados y antiguos',
                'path' => '/archives',
                'parent_id' => null,
                'icon' => 'fa-duotone fa-archive',
                'color' => '#6c757d',
                'position' => 4,
                'is_protected' => true,
                'is_active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Procesados',
                'key' => 'archives_processed',
                'description' => 'Archivos ya procesados',
                'path' => '/archives/processed',
                'parent_id' => null,
                'icon' => 'fa-duotone fa-check-square',
                'color' => '#198754',
                'position' => 1,
                'is_protected' => false,
                'is_active' => true,
            ],
            [
                'uid' => Str::ulid(),
                'name' => 'Histórico',
                'key' => 'archives_historical',
                'description' => 'Archivos históricos antiguos',
                'path' => '/archives/historical',
                'parent_id' => null,
                'icon' => 'fa-duotone fa-history',
                'color' => '#6c757d',
                'position' => 2,
                'is_protected' => false,
                'is_active' => true,
            ],
        ];

        foreach ($folders as $folder) {
            Setting\MediaFolder::firstOrCreate(
                ['key' => $folder['key']],
                $folder
            );
        }

        $this->command->info('✅ Media folders seeded successfully');
    }
}
