<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Conversation;

class ConversationParticipantsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:helpdesk.conversations.view')->only('index');
        $this->middleware('can:helpdesk.conversations.update')->only(['store', 'destroy']);
    }

    public function index(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $participants = $conversation->participants()
            ->select('users.id', 'firstname', 'lastname', 'email')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => trim($u->firstname.' '.$u->lastname),
                'email' => $u->email,
                'avatar' => $u->getAvatarUrl(),
            ]);

        return response()->json(['data' => $participants]);
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $conversation->participants()->syncWithoutDetaching($data['user_ids']);

        $participants = $conversation->participants()
            ->select('users.id', 'firstname', 'lastname', 'email')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => trim($u->firstname.' '.$u->lastname),
                'email' => $u->email,
                'avatar' => $u->getAvatarUrl(),
            ]);

        return response()->json(['success' => true, 'data' => $participants]);
    }

    public function destroy(Conversation $conversation, int $userId): JsonResponse
    {
        $this->authorize('update', $conversation);

        $conversation->participants()->detach($userId);

        return response()->json(['success' => true]);
    }
}
