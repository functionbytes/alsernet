<?php

namespace Modules\HelpdeskChatFlow\Services\Input;

use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;

/**
 * Customer replies on a `request_documents` node (document picks + files).
 */
class DocumentUploadInputHandler
{
    use RepliesOnNode;

    public function __construct(
        private readonly ChatFlowLocalizer $localizer,
    ) {}

    /**
     * Handles document uploads in a request_documents node.
     *
     * Channels that support file attachments (WP, FB, email) pass $attachmentUrls.
     * Text-only interaction: customer types the number of the doc they want to send,
     * and then sends a subsequent message with the file (handled in the next call).
     *
     * Logic:
     * - If $attachmentUrls not empty → assign first URL to the next pending doc type
     * - If $message is a number matching a pending doc → ask customer to send the file
     * - When all docs are collected → advance to next node
     */
    /**
     * @param  array<string, mixed>  $node
     * @param  array<string>  $attachmentUrls
     * @return string|null Node to continue from once every document arrived; null to keep waiting.
     */
    public function handle(ChatFlowSession $session, array $node, string $message, array $attachmentUrls): ?string
    {
        $data = $node['data'] ?? [];
        $required = $data['doc_types'] ?? [];
        $uploadKey = '_doc_uploads_'.$node['id'];
        $uploaded = $session->getContextValue($uploadKey) ?? [];
        $pending = array_values(array_diff($required, array_keys($uploaded)));

        $docLabels = config('helpdeskchatflow.document_labels', []);

        if (! empty($attachmentUrls)) {
            // File arrived — assign to pending doc that was announced (or first pending)
            $announcedKey = $session->getContextValue('_announced_doc_'.$node['id']);
            $docKey = ($announcedKey && in_array($announcedKey, $pending))
                ? $announcedKey
                : ($pending[0] ?? null);

            if ($docKey) {
                $uploaded[$docKey] = $attachmentUrls[0];
                $session->setContextValue($uploadKey, $uploaded);
                $session->setContextValue('_announced_doc_'.$node['id'], null);

                $label = $docLabels[$docKey] ?? $docKey;
                $this->sendBotMessage($session, $node, "✅ {$label} recibido. Gracias.");

                $pending = array_values(array_diff($required, array_keys($uploaded)));
            }
        } elseif (is_numeric(trim($message))) {
            // Customer typed a number to select which doc to send next
            $idx = (int) trim($message) - 1;
            $docKey = $pending[$idx] ?? null;

            if ($docKey) {
                $session->setContextValue('_announced_doc_'.$node['id'], $docKey);
                $label = $docLabels[$docKey] ?? $docKey;
                $this->sendBotMessage($session, $node, $this->localize($session, "Entendido. Por favor envía el archivo para **{$label}**."));

                return null; // Stay on this node waiting for the file
            }

            // Out-of-range number → tell the customer instead of going silent.
            $this->sendBotMessage($session, $node, $this->localize($session, 'Ese número no corresponde a ningún documento pendiente. Escribe el número de uno de la lista.'));

            return null;
        }

        if (empty($pending)) {
            // All documents received — save list and continue
            $varName = $data['variable_name'] ?? 'uploaded_docs';
            $session->setContextValue($varName, array_keys($uploaded));

            $doneMsg = $data['done_message'] ?? '📂 ¡Todos los documentos recibidos! Continuamos.';
            $this->sendBotMessage($session, $node, $doneMsg);

            return $this->firstChildId($session, $node['id']);
        }

        // Still pending — re-send the list of remaining docs
        $list = implode("\n", array_map(
            fn ($k, $t) => ($k + 1).'. '.($docLabels[$t] ?? $t),
            array_keys($pending),
            $pending
        ));
        $this->sendBotMessage($session, $node, "Aún faltan los siguientes documentos:\n{$list}\n\nEscribe el número del documento y envía el archivo.");

        return null;
    }
}
