<?php

namespace Modules\HelpdeskDocument\Services;

use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentStatus;
use Modules\Helpdesk\Models\Conversation;

/**
 * Crea un expediente del módulo Document para el cliente de una conversación
 * del inbox y lo vincula de inmediato. Cierra el hueco que dejaba el flujo de
 * importación desde chat ("pending real Document creation"): antes solo se
 * podían ver expedientes ya existentes.
 */
class ConversationDocumentCreator
{
    public function __construct(
        private readonly ConversationDocumentLinker $linker,
    ) {}

    public function create(Conversation $conversation, ?int $typeId = null, ?string $orderReference = null): Document
    {
        $customer = $conversation->customer;
        [$firstname, $lastname] = $this->splitName((string) ($customer?->name ?? ''));

        $document = Document::create([
            // null → el boot() de Document aplica el tipo por defecto ('dni').
            'type_id' => $typeId,
            'customer_email' => trim((string) ($customer?->email ?? '')),
            'customer_cellphone' => $customer?->phone ?: $customer?->whatsapp_phone,
            'customer_firstname' => $firstname,
            'customer_lastname' => $lastname,
            'order_reference' => $orderReference,
            'status_id' => DocumentStatus::query()->where('key', 'pending')->value('id'),
        ]);

        // La columna required_documents alimenta la lista y el progreso del
        // tab; se puebla con los requisitos configurados en el tipo.
        $required = array_keys($document->getRequiredDocumentsWithLabels());

        if ($required !== []) {
            $document->update(['required_documents' => $required]);
        }

        $this->linker->link($conversation, $document->fresh(['status', 'documentType']));

        return $document;
    }

    /**
     * Divide el nombre completo del cliente de helpdesk en nombre/apellidos
     * (el expediente los almacena por separado).
     *
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2, PREG_SPLIT_NO_EMPTY) ?: [];

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
