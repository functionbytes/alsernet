<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Document\Entities\DocumentPermission;

class DocumentPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Validation permissions
            [
                'name' => 'approve-documents',
                'label' => 'Aprobar documentos',
                'category' => 'validation',
                'description' => 'Permite aprobar documentos en el flujo de validación',
                'sort_order' => 1,
            ],
            [
                'name' => 'reject-documents',
                'label' => 'Rechazar documentos',
                'category' => 'validation',
                'description' => 'Permite rechazar documentos en el flujo de validación',
                'sort_order' => 2,
            ],
            [
                'name' => 'view-pending-documents',
                'label' => 'Ver documentos pendientes',
                'category' => 'validation',
                'description' => 'Permite ver documentos pendientes de validación',
                'sort_order' => 3,
            ],
            [
                'name' => 'assign-validators',
                'label' => 'Asignar validadores',
                'category' => 'validation',
                'description' => 'Permite asignar documentos a otros validadores',
                'sort_order' => 4,
            ],

            // File management permissions
            [
                'name' => 'upload-documents',
                'label' => 'Subir documentos',
                'category' => 'files',
                'description' => 'Permite subir archivos de documentos',
                'sort_order' => 10,
            ],
            [
                'name' => 'delete-documents',
                'label' => 'Eliminar documentos',
                'category' => 'files',
                'description' => 'Permite eliminar documentos y archivos',
                'sort_order' => 11,
            ],
            [
                'name' => 'view-documents',
                'label' => 'Ver documentos',
                'category' => 'files',
                'description' => 'Permite visualizar y descargar documentos',
                'sort_order' => 12,
            ],
            [
                'name' => 'upload-attachments',
                'label' => 'Subir adjuntos',
                'category' => 'files',
                'description' => 'Permite agregar archivos adjuntos adicionales',
                'sort_order' => 13,
            ],
            [
                'name' => 'delete-attachments',
                'label' => 'Eliminar adjuntos',
                'category' => 'files',
                'description' => 'Permite eliminar archivos adjuntos',
                'sort_order' => 14,
            ],

            // Email permissions
            [
                'name' => 'send-initial-request',
                'label' => 'Enviar solicitud inicial',
                'category' => 'emails',
                'description' => 'Permite enviar el email de solicitud inicial de documentos',
                'sort_order' => 20,
            ],
            [
                'name' => 'send-reminders',
                'label' => 'Enviar recordatorios',
                'category' => 'emails',
                'description' => 'Permite enviar emails de recordatorio',
                'sort_order' => 21,
            ],
            [
                'name' => 'send-missing-docs',
                'label' => 'Enviar documentos faltantes',
                'category' => 'emails',
                'description' => 'Permite enviar notificación de documentos faltantes',
                'sort_order' => 22,
            ],
            [
                'name' => 'send-upload-confirmation',
                'label' => 'Enviar confirmación de carga',
                'category' => 'emails',
                'description' => 'Permite enviar confirmación de carga de documentos',
                'sort_order' => 23,
            ],
            [
                'name' => 'send-approval',
                'label' => 'Enviar aprobación',
                'category' => 'emails',
                'description' => 'Permite enviar email de aprobación de documentos',
                'sort_order' => 24,
            ],
            [
                'name' => 'send-rejection',
                'label' => 'Enviar rechazo',
                'category' => 'emails',
                'description' => 'Permite enviar email de rechazo de documentos',
                'sort_order' => 25,
            ],
            [
                'name' => 'send-custom-email',
                'label' => 'Enviar email personalizado',
                'category' => 'emails',
                'description' => 'Permite enviar emails personalizados',
                'sort_order' => 26,
            ],

            // Document management permissions
            [
                'name' => 'create-documents',
                'label' => 'Crear documentos',
                'category' => 'management',
                'description' => 'Permite crear nuevos registros de documentos',
                'sort_order' => 30,
            ],
            [
                'name' => 'edit-documents',
                'label' => 'Editar documentos',
                'category' => 'management',
                'description' => 'Permite editar información de documentos',
                'sort_order' => 31,
            ],
            [
                'name' => 'add-notes',
                'label' => 'Agregar notas',
                'category' => 'management',
                'description' => 'Permite agregar notas internas a documentos',
                'sort_order' => 33,
            ],
            [
                'name' => 'edit-notes',
                'label' => 'Editar notas',
                'category' => 'management',
                'description' => 'Permite editar notas existentes',
                'sort_order' => 34,
            ],
            [
                'name' => 'delete-notes',
                'label' => 'Eliminar notas',
                'category' => 'management',
                'description' => 'Permite eliminar notas',
                'sort_order' => 35,
            ],

            // Field-level document editing permissions
            [
                'name' => 'edit-status',
                'label' => 'Editar estado del documento',
                'category' => 'management',
                'description' => 'Permite editar el estado del documento',
                'sort_order' => 36,
            ],
            [
                'name' => 'edit-source',
                'label' => 'Editar origen del documento',
                'category' => 'management',
                'description' => 'Permite editar el origen/canal del documento',
                'sort_order' => 37,
            ],
            [
                'name' => 'edit-load',
                'label' => 'Editar método de carga',
                'category' => 'management',
                'description' => 'Permite editar el método de carga del documento',
                'sort_order' => 38,
            ],
            [
                'name' => 'edit-sync',
                'label' => 'Editar tipo de sincronización',
                'category' => 'management',
                'description' => 'Permite editar el tipo de sincronización del documento',
                'sort_order' => 39,
            ],
            [
                'name' => 'edit-upload',
                'label' => 'Editar tipo de subida',
                'category' => 'management',
                'description' => 'Permite editar el tipo de subida del documento',
                'sort_order' => 40,
            ],
            [
                'name' => 'edit-financing',
                'label' => 'Editar requisito de financiación',
                'category' => 'management',
                'description' => 'Permite editar si el documento requiere financiación',
                'sort_order' => 41,
            ],

            // Import/Sync permissions
            [
                'name' => 'import-documents',
                'label' => 'Importar documentos',
                'category' => 'sync',
                'description' => 'Permite importar documentos desde sistemas externos',
                'sort_order' => 50,
            ],
            [
                'name' => 'sync-erp',
                'label' => 'Sincronizar con ERP',
                'category' => 'sync',
                'description' => 'Permite sincronizar documentos con el ERP',
                'sort_order' => 51,
            ],
            [
                'name' => 'sync-api',
                'label' => 'Sincronizar por API',
                'category' => 'sync',
                'description' => 'Permite sincronizar documentos mediante API',
                'sort_order' => 52,
            ],

            // Advanced permissions
            [
                'name' => 'manage-blockades',
                'label' => 'Gestionar bloqueos',
                'category' => 'advanced',
                'description' => 'Permite gestionar bloqueos de productos',
                'sort_order' => 60,
            ],
            [
                'name' => 'view-history',
                'label' => 'Ver historial',
                'category' => 'advanced',
                'description' => 'Permite ver historial completo de cambios',
                'sort_order' => 61,
            ],
            [
                'name' => 'export-documents',
                'label' => 'Exportar documentos',
                'category' => 'advanced',
                'description' => 'Permite exportar documentos y reportes',
                'sort_order' => 62,
            ],

            // Route permissions - Control de acceso a rutas
            [
                'name' => 'route-all-documents',
                'label' => 'Ver todos los documentos',
                'category' => 'route',
                'description' => 'Acceso a la ruta GET / (listado completo de documentos)',
                'sort_order' => 70,
            ],
            [
                'name' => 'route-pending-documents',
                'label' => 'Ver documentos pendientes de validación',
                'category' => 'route',
                'description' => 'Acceso a la ruta GET /pending (documentos pendientes en etapa de validación del usuario)',
                'sort_order' => 71,
            ],
            [
                'name' => 'route-show-documents',
                'label' => 'Ver documentos individuales',
                'category' => 'route',
                'description' => 'Acceso a rutas de ver documentos: show, summary',
                'sort_order' => 72,
            ],
            [
                'name' => 'route-manage-documents',
                'label' => 'Gestionar documentos',
                'category' => 'route',
                'description' => 'Acceso a la ruta GET /manage/{uid} (gestión detallada)',
                'sort_order' => 73,
            ],
            [
                'name' => 'route-sync-documents',
                'label' => 'Importar documentos',
                'category' => 'route',
                'description' => 'Acceso general a rutas de sincronización e importación',
                'sort_order' => 74,
            ],
            [
                'name' => 'route-sync-api-documents',
                'label' => 'Sincronizar desde API',
                'category' => 'route',
                'description' => 'Acceso a la ruta GET /import/api (sincronización desde PrestaShop API)',
                'sort_order' => 75,
            ],
            [
                'name' => 'route-sync-erp-documents',
                'label' => 'Sincronizar desde ERP',
                'category' => 'route',
                'description' => 'Acceso a la ruta GET /import/erp (sincronización desde ERP)',
                'sort_order' => 76,
            ],
            [
                'name' => 'route-mails-documents',
                'label' => 'Ver emails de documentos',
                'category' => 'route',
                'description' => 'Acceso a la vista de emails asociados a un documento',
                'sort_order' => 77,
            ],
        ];

        foreach ($permissions as $permissionData) {
            DocumentPermission::findOrCreateByName($permissionData['name'], $permissionData);
        }

        $this->command->info('Permisos de documentos creados exitosamente.');
    }
}
