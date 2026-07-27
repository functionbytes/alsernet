<?php

namespace Modules\HelpdeskDocument\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentValidatorGroup;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskDocument\Concerns\AuthorizesConversationDocuments;
use Modules\HelpdeskDocument\Services\ConversationDocumentLinker;
use Modules\HelpdeskDocument\Services\DocumentPanelPresenter;

class DocumentPanelController extends Controller
{
    use AuthorizesConversationDocuments;

    /**
     * Re-renderiza la lista de expedientes del tab (refresco AJAX: polling
     * suave del inbox y tras crear/importar/subir). Mantiene el vínculo
     * conversación↔expediente al día igual que la hidratación inicial.
     */
    public function list(
        Conversation $conversation,
        ConversationDocumentLinker $linker,
        DocumentPanelPresenter $presenter
    ): View {
        $customer = $conversation->customer;

        if ($customer) {
            $this->authorize('view', $customer);
        }

        $documents = $linker->documentsForConversation($conversation);
        $linker->syncLink($conversation, $documents);

        return view('helpdeskdocument::inbox-slots._document-list', [
            'rpDocuments' => $presenter->list($documents),
            'rpConvo' => $conversation,
        ]);
    }

    /**
     * Render the full detail panel (docs-rp) for one of the customer's
     * documents. Loaded via AJAX into the inbox right-panel when an agent
     * opens an expediente from the document list.
     */
    public function show(
        Conversation $conversation,
        Document $document,
        DocumentPanelPresenter $presenter
    ): View {
        $this->assertDocumentBelongsToConversation($conversation, $document);

        return view('helpdeskdocument::inbox-slots._document-detail', [
            'rpDocument' => $presenter->present($document),
            'rpConvo' => $conversation,
            'rpAssignees' => $this->validatorAssignees(),
        ]);
    }

    /**
     * Flat list of users across all validator groups, for the "assign" dropdown.
     * Cached briefly since validator groups/users change rarely but this is
     * queried on every expediente opened in the inbox panel.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function validatorAssignees(): array
    {
        try {
            return Cache::remember('document.validator-assignees', now()->addMinutes(5), function () {
                return DocumentValidatorGroup::with('users')->get()
                    ->pluck('users')->flatten()->unique('id')
                    ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name ?? $u->full_name ?? ('Usuario #'.$u->id)])
                    ->values()->all();
            });
        } catch (\Throwable $e) {
            Log::warning('DocumentPanelController: no se pudieron cargar los validadores asignables', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
