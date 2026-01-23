<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Document\Entities\DocumentPermission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add component-level permissions that appear in settings UI
        $componentPermissions = [
            [
                'name' => 'view-email-actions',
                'label' => 'Ver acciones de email',
                'category' => 'components',
                'description' => 'Permite ver y usar panel de acciones de email en documentos',
                'sort_order' => 1,
            ],
            [
                'name' => 'view-validation-workflow',
                'label' => 'Ver flujo de validación',
                'category' => 'components',
                'description' => 'Permite ver y usar panel de validación de documentos',
                'sort_order' => 2,
            ],
            [
                'name' => 'view-document-notes',
                'label' => 'Ver notas de documento',
                'category' => 'components',
                'description' => 'Permite ver y agregar notas en documentos',
                'sort_order' => 3,
            ],
            [
                'name' => 'view-action-history',
                'label' => 'Ver historial de acciones',
                'category' => 'components',
                'description' => 'Permite ver el historial de todas las acciones en documentos',
                'sort_order' => 4,
            ],
            [
                'name' => 'view-email-history',
                'label' => 'Ver historial de emails',
                'category' => 'components',
                'description' => 'Permite ver el historial de emails enviados en documentos',
                'sort_order' => 5,
            ],
            [
                'name' => 'view-status-timeline',
                'label' => 'Ver línea de tiempo de estado',
                'category' => 'components',
                'description' => 'Permite ver la línea de tiempo de cambios de estado',
                'sort_order' => 6,
            ],
            [
                'name' => 'view-order-details',
                'label' => 'Ver detalles de orden',
                'category' => 'components',
                'description' => 'Permite ver detalles de la orden asociada',
                'sort_order' => 7,
            ],
            [
                'name' => 'view-customer-information',
                'label' => 'Ver información del cliente',
                'category' => 'components',
                'description' => 'Permite ver datos de contacto del cliente',
                'sort_order' => 8,
            ],
            [
                'name' => 'view-products-list',
                'label' => 'Ver lista de productos',
                'category' => 'components',
                'description' => 'Permite ver productos asociados al documento',
                'sort_order' => 9,
            ],
            [
                'name' => 'view-document-management',
                'label' => 'Ver gestión de documentos',
                'category' => 'components',
                'description' => 'Permite ver panel de configuración del documento',
                'sort_order' => 10,
            ],
            [
                'name' => 'view-document-upload',
                'label' => 'Ver carga de documentos',
                'category' => 'components',
                'description' => 'Permite ver y usar sección de carga de archivos',
                'sort_order' => 11,
            ],
            [
                'name' => 'view-additional-attachments',
                'label' => 'Ver archivos adicionales',
                'category' => 'components',
                'description' => 'Permite ver y cargar archivos adjuntos adicionales',
                'sort_order' => 12,
            ],
        ];

        // Create or update permissions
        foreach ($componentPermissions as $permission) {
            DocumentPermission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete component permissions
        $componentNames = [
            'view-email-actions',
            'view-validation-workflow',
            'view-document-notes',
            'view-action-history',
            'view-email-history',
            'view-status-timeline',
            'view-order-details',
            'view-customer-information',
            'view-products-list',
            'view-document-management',
            'view-document-upload',
            'view-additional-attachments',
        ];

        DocumentPermission::whereIn('name', $componentNames)->delete();
    }
};
