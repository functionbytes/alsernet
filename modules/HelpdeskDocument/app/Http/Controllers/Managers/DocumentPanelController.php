<?php

namespace Modules\HelpdeskDocument\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentValidatorGroup;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskDocument\Services\DocumentPanelPresenter;

class DocumentPanelController extends Controller
{
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
        $customer = $conversation->customer;

        if ($customer) {
            $this->authorize('view', $customer);
        }

        // The document must belong to the conversation's customer (email match),
        // so an agent cannot pull an unrelated expediente through this endpoint.
        $conversationEmail = mb_strtolower(trim((string) $customer?->email));
        $documentEmail = mb_strtolower(trim((string) $document->customer_email));

        abort_if($conversationEmail === '' || $conversationEmail !== $documentEmail, 404);

        return view('helpdeskdocument::inbox-slots._document-detail', [
            'rpDocument' => $presenter->present($document),
            'rpConvo' => $conversation,
            'rpAssignees' => $this->validatorAssignees(),
        ]);
    }

    /**
     * Flat list of users across all validator groups, for the "assign" dropdown.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function validatorAssignees(): array
    {
        try {
            return DocumentValidatorGroup::with('users')->get()
                ->pluck('users')->flatten()->unique('id')
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name ?? $u->full_name ?? ('Usuario #'.$u->id)])
                ->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
