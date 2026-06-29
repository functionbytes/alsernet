<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTickets\Events\NewTicketMessage;
use Modules\HelpdeskTickets\Events\TicketMessageReceived;
use Modules\HelpdeskTickets\Events\TicketTyping;
use Modules\HelpdeskTickets\Http\Requests\Managers\BulkReplyTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\Managers\StoreTicketMessageRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Services\MentionService;
use Modules\HelpdeskTickets\Services\TicketAiService;

class TicketMessagingController extends Controller
{
    public function __construct(
        private readonly MentionService $mentionService,
    ) {}

    public function storeMessage(StoreTicketMessageRequest $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $ticket);

        $validated = $request->validated();

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachmentPaths[] = $file->store(
                    'helpdesk/tickets/'.$ticket->id,
                    config('helpdesk.attachments.disk', 'local')
                );
            }
        }

        $item = $ticket->items()->create([
            'type' => 'message',
            'user_id' => auth()->id(),
            'body' => $validated['body'],
            'attachment_urls' => $attachmentPaths,
            'is_internal' => $request->boolean('is_internal', false),
        ]);

        $data = ['last_message_at' => now()];
        if (! $ticket->first_response_at) {
            $data['first_response_at'] = now();
        }
        $ticket->update($data);

        $this->mentionService->notifyMentions($request->body, $ticket);

        MessageAdded::dispatch($item);

        broadcast(new TicketMessageReceived($ticket, $item));
        NewTicketMessage::dispatch($ticket, [
            'id' => $item->id,
            'content' => $item->body,
            'author' => auth()->user()->name,
            'created_at' => $item->created_at->toIso8601String(),
            'type' => 'agent',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesktickets::helpdesktickets.messages.message_sent'),
                'item' => $item->load(['user']),
            ]);
        }

        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesktickets::helpdesktickets.messages.message_sent'));
    }

    public function bulkReply(BulkReplyTicketRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $validated = $request->validated();

        $isInternal = (bool) ($validated['is_internal'] ?? false);
        $count = count($validated['ticket_ids']);
        $ids = $validated['ticket_ids'];
        $now = now();

        DB::transaction(function () use ($ids, $validated, $isInternal, $now): void {
            $validIds = Ticket::whereIn('id', $ids)->pluck('id')->all();

            if (empty($validIds)) {
                return;
            }

            $items = array_map(fn ($id) => [
                'ticket_id' => $id,
                'type' => 'message',
                'user_id' => auth()->id(),
                'body' => $validated['body'],
                'is_internal' => $isInternal,
                'created_at' => $now,
                'updated_at' => $now,
            ], $validIds);

            TicketItem::insert($items);

            Ticket::whereIn('id', $validIds)->update(['last_message_at' => $now]);

            Ticket::whereIn('id', $validIds)
                ->whereNull('first_response_at')
                ->update(['first_response_at' => $now]);
        });

        return response()->json([
            'success' => true,
            'message' => "Respuesta enviada a {$count} ".($count === 1 ? 'ticket' : 'tickets').'.',
        ]);
    }

    public function typing(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        broadcast(new TicketTyping(
            ticketId: $ticket->id,
            userId: $user->id,
            userName: trim($user->firstname.' '.$user->lastname),
            isTyping: $request->boolean('is_typing', true),
        ))->toOthers();

        return response()->json(['ok' => true]);
    }

    public function smartReplies(Ticket $ticket, TicketAiService $ai): JsonResponse
    {
        return response()->json(['suggestions' => $ai->smartReplySuggestions($ticket)]);
    }
}
