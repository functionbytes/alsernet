<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Document\Entities\DocumentPermission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Define all component-based permissions with their metadata
        $permissions = [
            // Email Actions Component
            [
                'name' => 'send-initial-request',
                'label' => 'Enviar solicitud inicial',
                'category' => 'emails',
                'description' => 'Permite enviar solicitud inicial de documentos al cliente',
                'sort_order' => 10,
            ],
            [
                'name' => 'send-reminders',
                'label' => 'Enviar recordatorios',
                'category' => 'emails',
                'description' => 'Permite enviar recordatorios de documentos pendientes',
                'sort_order' => 11,
            ],
            [
                'name' => 'send-missing-docs',
                'label' => 'Notificar documentos faltantes',
                'category' => 'emails',
                'description' => 'Permite notificar al cliente sobre documentos faltantes',
                'sort_order' => 12,
            ],
            [
                'name' => 'send-approval',
                'label' => 'Enviar confirmación de aprobación',
                'category' => 'emails',
                'description' => 'Permite enviar confirmación cuando se aprueba un documento',
                'sort_order' => 13,
            ],
            [
                'name' => 'send-rejection',
                'label' => 'Enviar notificación de rechazo',
                'category' => 'emails',
                'description' => 'Permite enviar notificación cuando se rechaza un documento',
                'sort_order' => 14,
            ],
            [
                'name' => 'send-custom-email',
                'label' => 'Enviar email personalizado',
                'category' => 'emails',
                'description' => 'Permite enviar emails personalizados al cliente',
                'sort_order' => 15,
            ],

            // Document Management Component
            [
                'name' => 'edit-documents',
                'label' => 'Editar documentos',
                'category' => 'management',
                'description' => 'Permite editar la información de documentos',
                'sort_order' => 20,
            ],
            [
                'name' => 'create-documents',
                'label' => 'Crear documentos',
                'category' => 'management',
                'description' => 'Permite crear nuevos documentos',
                'sort_order' => 21,
            ],
            [
                'name' => 'view-all-documents',
                'label' => 'Ver todos los documentos',
                'category' => 'management',
                'description' => 'Permite ver la información completa de todos los documentos',
                'sort_order' => 22,
            ],

            // Document Upload Component
            [
                'name' => 'upload-documents',
                'label' => 'Cargar documentos',
                'category' => 'files',
                'description' => 'Permite cargar archivos de documentos',
                'sort_order' => 30,
            ],
            [
                'name' => 'delete-documents',
                'label' => 'Eliminar documentos',
                'category' => 'files',
                'description' => 'Permite eliminar documentos y archivos',
                'sort_order' => 31,
            ],

            // Validation Workflow Component
            [
                'name' => 'approve-documents',
                'label' => 'Aprobar documentos',
                'category' => 'validation',
                'description' => 'Permite aprobar documentos en flujos de validación',
                'sort_order' => 40,
            ],
            [
                'name' => 'reject-documents',
                'label' => 'Rechazar documentos',
                'category' => 'validation',
                'description' => 'Permite rechazar documentos en flujos de validación',
                'sort_order' => 41,
            ],

            // Document Notes Component
            [
                'name' => 'add-notes',
                'label' => 'Agregar notas',
                'category' => 'management',
                'description' => 'Permite agregar notas en documentos',
                'sort_order' => 50,
            ],
            [
                'name' => 'edit-notes',
                'label' => 'Editar notas',
                'category' => 'management',
                'description' => 'Permite editar notas existentes de documentos',
                'sort_order' => 51,
            ],
            [
                'name' => 'delete-notes',
                'label' => 'Eliminar notas',
                'category' => 'management',
                'description' => 'Permite eliminar notas de documentos',
                'sort_order' => 52,
            ],

            // Additional Attachments Component
            [
                'name' => 'upload-attachments',
                'label' => 'Cargar archivos adicionales',
                'category' => 'files',
                'description' => 'Permite cargar archivos adjuntos adicionales',
                'sort_order' => 60,
            ],
            [
                'name' => 'delete-attachments',
                'label' => 'Eliminar archivos adjuntos',
                'category' => 'files',
                'description' => 'Permite eliminar archivos adjuntos',
                'sort_order' => 61,
            ],

            // Sync Operations Component
            [
                'name' => 'import-documents',
                'label' => 'Importar documentos',
                'category' => 'sync',
                'description' => 'Permite importar documentos desde ordenes',
                'sort_order' => 70,
            ],
            [
                'name' => 'sync-erp',
                'label' => 'Sincronizar con ERP',
                'category' => 'sync',
                'description' => 'Permite sincronizar documentos con sistemas ERP',
                'sort_order' => 71,
            ],
            [
                'name' => 'sync-api',
                'label' => 'Sincronizar por API',
                'category' => 'sync',
                'description' => 'Permite sincronizar documentos a través de API',
                'sort_order' => 72,
            ],

            // Additional View/History Permissions
            [
                'name' => 'view-documents',
                'label' => 'Ver documentos',
                'category' => 'files',
                'description' => 'Permite ver documentos cargados',
                'sort_order' => 80,
            ],
            [
                'name' => 'view-pending-documents',
                'label' => 'Ver documentos pendientes',
                'category' => 'validation',
                'description' => 'Permite ver documentos pendientes de validación',
                'sort_order' => 81,
            ],
            [
                'name' => 'view-history',
                'label' => 'Ver historial',
                'category' => 'advanced',
                'description' => 'Permite ver historial completo de acciones en documentos',
                'sort_order' => 90,
            ],
            [
                'name' => 'assign-validators',
                'label' => 'Asignar validadores',
                'category' => 'validation',
                'description' => 'Permite asignar validadores a documentos',
                'sort_order' => 82,
            ],
            [
                'name' => 'export-documents',
                'label' => 'Exportar documentos',
                'category' => 'advanced',
                'description' => 'Permite exportar documentos a diferentes formatos',
                'sort_order' => 91,
            ],
            [
                'name' => 'manage-blockades',
                'label' => 'Gestionar bloqueos',
                'category' => 'advanced',
                'description' => 'Permite gestionar bloqueos de documentos',
                'sort_order' => 92,
            ],
            [
                'name' => 'send-upload-confirmation',
                'label' => 'Enviar confirmación de carga',
                'category' => 'emails',
                'description' => 'Permite enviar confirmación cuando el cliente carga documentos',
                'sort_order' => 16,
            ],
        ];

        // Sync permissions in database
        foreach ($permissions as $permissionData) {
            DocumentPermission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to delete, just revert to previous state
    }
};
