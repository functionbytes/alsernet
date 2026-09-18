<?php

namespace Modules\Helpdesk\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Portal\SendMessageRequest;
use Modules\Helpdesk\Http\Requests\Portal\UpdateProfileRequest;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;

class PortalDashboardController extends Controller
{
    public function dashboard(Request $request): View
    {
        /** @var Customer $customer */
        $customer = $request->get('portal_customer');

        $conversations = Conversation::query()
            ->where('customer_id', $customer->id)
            ->with(['status', 'inbox'])
            ->latest('last_message_at')
            ->paginate(15);

        return view('helpdesk::public.portal.dashboard', compact('customer', 'conversations'));
    }

    public function showConversation(Request $request, int $id): View
    {
        /** @var Customer $customer */
        $customer = $request->get('portal_customer');

        $conversation = Conversation::query()
            ->where('customer_id', $customer->id)
            ->with(['status', 'inbox', 'items' => fn ($q) => $q->reorder('created_at', 'desc')->limit(200)])
            ->findOrFail($id);

        // La vista pinta el hilo en orden ascendente (más antiguo arriba, scroll al final abajo)
        $conversation->setRelation('items', $conversation->items->sortBy('created_at')->values());

        return view('helpdesk::public.portal.conversation', compact('customer', 'conversation'));
    }

    public function sendMessage(SendMessageRequest $request, int $id): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->get('portal_customer');

        $conversation = Conversation::query()
            ->where('customer_id', $customer->id)
            ->findOrFail($id);

        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'type' => 'message',
            'body' => $request->validated('message'),
            'author_id' => $customer->id,
            'metadata' => ['source' => 'portal', 'customer_id' => $customer->id],
        ]);

        $conversation->touch('last_message_at');

        return redirect()->route('helpdesk.portal.conversation', $id)
            ->with('success', 'Mensaje enviado correctamente.');
    }

    public function profile(Request $request): View
    {
        /** @var Customer $customer */
        $customer = $request->get('portal_customer');

        return view('helpdesk::public.portal.profile', compact('customer'));
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->get('portal_customer');

        $customer->update($request->validated());

        return back()->with('success', 'Perfil actualizado correctamente.');
    }
}
