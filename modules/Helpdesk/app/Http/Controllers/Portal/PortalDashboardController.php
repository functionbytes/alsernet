<?php

namespace Modules\Helpdesk\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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

        return view('helpdesk::portal.dashboard', compact('customer', 'conversations'));
    }

    public function showConversation(Request $request, int $id): View
    {
        /** @var Customer $customer */
        $customer = $request->get('portal_customer');

        $conversation = Conversation::query()
            ->where('customer_id', $customer->id)
            ->with(['status', 'inbox', 'items' => fn ($q) => $q->orderBy('created_at')])
            ->findOrFail($id);

        return view('helpdesk::portal.conversation', compact('customer', 'conversation'));
    }

    public function sendMessage(Request $request, int $id): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->get('portal_customer');

        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'message.required' => 'El mensaje no puede estar vacio.',
            'message.max' => 'El mensaje no puede superar los 5000 caracteres.',
        ]);

        $conversation = Conversation::query()
            ->where('customer_id', $customer->id)
            ->findOrFail($id);

        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'type' => 'message',
            'body' => $request->message,
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

        return view('helpdesk::portal.profile', compact('customer'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->get('portal_customer');

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
        ]);

        $customer->update($request->only('name', 'phone'));

        return back()->with('success', 'Perfil actualizado correctamente.');
    }
}
