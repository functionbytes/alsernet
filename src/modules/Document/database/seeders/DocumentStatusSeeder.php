<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Document\Entities\DocumentStatus;

class DocumentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'key' => 'pending',
                'label' => 'Solicitado',
                'description' => 'Documentación solicitada. Email de solicitud enviado. Esperando que el cliente envíe documentos.',
                'color' => '#6c757d',
                'icon' => 'fa-duotone file-text',
                'is_active' => true,
                'order' => 1,
                'translations' => [
                    ['lang_id' => 1, 'label' => 'Solicitado', 'description' => 'Documentación solicitada. Email de solicitud enviado.'],
                    ['lang_id' => 2, 'label' => 'Requested', 'description' => 'Documentation requested. Request email sent.'],
                    ['lang_id' => 3, 'label' => 'Demandé', 'description' => 'Documentation demandée. Email de demande envoyé.'],
                    ['lang_id' => 4, 'label' => 'Solicitado', 'description' => 'Documentação solicitada. Email de solicitação enviado.'],
                    ['lang_id' => 5, 'label' => 'Angefordert', 'description' => 'Dokumentation angefordert. Anforderungs-E-Mail gesendet.'],
                ],
            ],
            [
                'key' => 'awaiting_documents',
                'label' => 'Esperando documentos',
                'description' => 'Cliente no ha enviado documentos. Recordatorios enviados periódicamente.',
                'color' => '#17a2b8',
                'icon' => 'fa-duotone hourglass',
                'is_active' => true,
                'order' => 2,
                'translations' => [
                    ['lang_id' => 1, 'label' => 'Esperando documentos', 'description' => 'Cliente no ha enviado documentos.'],
                    ['lang_id' => 2, 'label' => 'Awaiting Documents', 'description' => 'Customer has not sent documents.'],
                    ['lang_id' => 3, 'label' => 'En attente de documents', 'description' => 'Le client n\'a pas envoyé de documents.'],
                    ['lang_id' => 4, 'label' => 'Aguardando documentos', 'description' => 'O cliente não enviou documentos.'],
                    ['lang_id' => 5, 'label' => 'Auf Dokumente warten', 'description' => 'Der Kunde hat keine Dokumente eingereicht.'],
                ],
            ],
            [
                'key' => 'received',
                'label' => 'Documentos recibidos',
                'description' => 'Documentos recibidos del cliente. Email de confirmación enviado. En espera de revisión del administrador.',
                'color' => '#0dcaf0',
                'icon' => 'fa-duotone inbox',
                'is_active' => true,
                'order' => 3,
                'translations' => [
                    ['lang_id' => 1, 'label' => 'Documentos recibidos', 'description' => 'Documentos recibidos del cliente.'],
                    ['lang_id' => 2, 'label' => 'Documents Received', 'description' => 'Documents received from customer.'],
                    ['lang_id' => 3, 'label' => 'Documents reçus', 'description' => 'Documents reçus du client.'],
                    ['lang_id' => 4, 'label' => 'Documentos recebidos', 'description' => 'Documentos recebidos do cliente.'],
                    ['lang_id' => 5, 'label' => 'Dokumente erhalten', 'description' => 'Dokumente vom Kunden erhalten.'],
                ],
            ],
            [
                'key' => 'incomplete',
                'label' => 'Incompleto',
                'description' => 'Faltan documentos requeridos después de la revisión del administrador.',
                'color' => '#ffc107',
                'icon' => 'fa-duotone alert-circle',
                'is_active' => true,
                'order' => 4,
                'translations' => [
                    ['lang_id' => 1, 'label' => 'Incompleto', 'description' => 'Faltan documentos requeridos.'],
                    ['lang_id' => 2, 'label' => 'Incomplete', 'description' => 'Required documents are missing.'],
                    ['lang_id' => 3, 'label' => 'Incomplet', 'description' => 'Les documents requis sont manquants.'],
                    ['lang_id' => 4, 'label' => 'Incompleto', 'description' => 'Faltam documentos necessários.'],
                    ['lang_id' => 5, 'label' => 'Unvollständig', 'description' => 'Erforderliche Dokumente fehlen.'],
                ],
            ],
            [
                'key' => 'approved',
                'label' => 'Aprobado',
                'description' => 'Documentos verificados y aprobados. Email de aprobación enviado. Solicitud completada.',
                'color' => '#28a745',
                'icon' => 'fa-duotone check-circle',
                'is_active' => true,
                'order' => 5,
                'translations' => [
                    ['lang_id' => 1, 'label' => 'Aprobado', 'description' => 'Documentos verificados y aprobados.'],
                    ['lang_id' => 2, 'label' => 'Approved', 'description' => 'Documents verified and approved.'],
                    ['lang_id' => 3, 'label' => 'Approuvé', 'description' => 'Documents vérifiés et approuvés.'],
                    ['lang_id' => 4, 'label' => 'Aprovado', 'description' => 'Documentos verificados e aprovados.'],
                    ['lang_id' => 5, 'label' => 'Genehmigt', 'description' => 'Dokumente überprüft und genehmigt.'],
                ],
            ],
            [
                'key' => 'rejected',
                'label' => 'Rechazado',
                'description' => 'Documentos rechazados con motivo. Email de rechazo enviado. Cliente debe reenviar documentación.',
                'color' => '#dc3545',
                'icon' => 'fa-duotone x-circle',
                'is_active' => true,
                'order' => 6,
                'translations' => [
                    ['lang_id' => 1, 'label' => 'Rechazado', 'description' => 'Documentos rechazados con motivo.'],
                    ['lang_id' => 2, 'label' => 'Rejected', 'description' => 'Documents rejected with reason.'],
                    ['lang_id' => 3, 'label' => 'Rejeté', 'description' => 'Documents rejetés avec motif.'],
                    ['lang_id' => 4, 'label' => 'Rejeitado', 'description' => 'Documentos rejeitados com motivo.'],
                    ['lang_id' => 5, 'label' => 'Abgelehnt', 'description' => 'Dokumente mit Begründung abgelehnt.'],
                ],
            ],
            [
                'key' => 'cancelled',
                'label' => 'Cancelado',
                'description' => 'Solicitud de documento cancelada por el administrador.',
                'color' => '#6c757d',
                'icon' => 'fa-duotone ban',
                'is_active' => true,
                'order' => 7,
                'translations' => [
                    ['lang_id' => 1, 'label' => 'Cancelado', 'description' => 'Solicitud cancelada por el administrador.'],
                    ['lang_id' => 2, 'label' => 'Cancelled', 'description' => 'Request cancelled by administrator.'],
                    ['lang_id' => 3, 'label' => 'Annulé', 'description' => 'Demande annulée par l\'administrateur.'],
                    ['lang_id' => 4, 'label' => 'Cancelado', 'description' => 'Solicitação cancelada pelo administrador.'],
                    ['lang_id' => 5, 'label' => 'Storniert', 'description' => 'Anforderung vom Administrator storniert.'],
                ],
            ],
            [
                'key' => 'completed',
                'label' => 'Completado',
                'description' => 'Status obsoleto. Use "Approved" en su lugar.',
                'color' => '#20c997',
                'icon' => 'fa-duotone badge-check',
                'is_active' => false,
                'order' => 8,
                'translations' => [
                    ['lang_id' => 1, 'label' => 'Completado', 'description' => 'Estado obsoleto.'],
                    ['lang_id' => 2, 'label' => 'Completed', 'description' => 'Obsolete status.'],
                    ['lang_id' => 3, 'label' => 'Complété', 'description' => 'État obsolète.'],
                    ['lang_id' => 4, 'label' => 'Completo', 'description' => 'Status obsoleto.'],
                    ['lang_id' => 5, 'label' => 'Fertig', 'description' => 'Veralteter Status.'],
                ],
            ],
        ];

        try {
            DB::beginTransaction();

            foreach ($statuses as $statusData) {
                $translations = $statusData['translations'] ?? [];
                unset($statusData['translations']);

                $status = DocumentStatus::updateOrCreate(
                    ['key' => $statusData['key']],
                    $statusData
                );

                // Add translations
                if (! empty($translations)) {
                    foreach ($translations as $translation) {
                        DB::table('document_status_langs')->updateOrInsert(
                            [
                                'document_status_id' => $status->id,
                                'lang_id' => $translation['lang_id'],
                            ],
                            [
                                'uid' => (string) Str::ulid(),
                                'label' => $translation['label'],
                                'description' => $translation['description'] ?? '',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }
            }

            DB::commit();
            $this->command->info('✅ Document statuses seeded successfully with multilingual translations');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error seeding document statuses: '.$e->getMessage());
        }
    }
}
