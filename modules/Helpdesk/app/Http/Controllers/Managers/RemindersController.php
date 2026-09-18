<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\StoreReminderRequest;
use Modules\Helpdesk\Jobs\SendReminderNotificationJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Reminder;

class RemindersController extends Controller
{
    /**
     * POST /panel/helpdesk/reminders
     * POST /panel/helpdesk/conversations/{conversation}/reminders
     * Create a personal reminder for the current agent (#59 ve-reminder).
     */
    public function store(StoreReminderRequest $request, ?Conversation $conversation = null): JsonResponse
    {
        // Evita IDOR: el permiso genérico del FormRequest no basta; hay que verificar
        // que el agente puede ver esta conversación concreta (policy con scope de inbox).
        if ($conversation) {
            $this->authorize('view', $conversation);
        }

        $reminder = Reminder::create([
            'user_id' => $request->user()->id,
            'conversation_id' => $conversation?->id,
            'title' => $request->validated('title'),
            'notes' => $request->validated('notes'),
            'remind_at' => $request->date('remind_at'),
            'email_notify' => $request->boolean('email_notify'),
        ]);

        SendReminderNotificationJob::dispatch($reminder->id)->delay($reminder->remind_at);

        return response()->json([
            'success' => true,
            'message' => 'Recordatorio creado',
            'reminder' => [
                'id' => $reminder->id,
                'title' => $reminder->title,
                'remindAt' => $reminder->remind_at->toIso8601String(),
            ],
        ], 201);
    }
}
