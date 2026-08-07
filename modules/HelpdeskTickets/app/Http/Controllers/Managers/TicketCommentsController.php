<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Http\Requests\StoreTicketCommentRequest;
use Modules\HelpdeskTickets\Http\Requests\UpdateTicketCommentRequest;
use Modules\HelpdeskTickets\Mail\TicketReplyMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketComment;
use Modules\HelpdeskTickets\Models\TicketHistory;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Support\TicketMailRenderer;

class TicketCommentsController extends Controller
{
    /**
     * Display a listing of comments for a ticket.
     */
    public function index(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $comments = $ticket->comments()
            ->with(['user', 'author', 'editor'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($comments);
    }

    /**
     * Store a newly created comment in storage.
     */
    public function store(StoreTicketCommentRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('create', TicketComment::class);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'body' => $request->input('body'),
            'html_body' => $request->input('html_body'),
            'is_internal' => $request->boolean('is_internal', false),
            'attachment_urls' => $request->input('attachment_urls'),
            'mentioned_user_ids' => $request->input('mentioned_user_ids', []),
        ]);

        // Log history
        TicketHistory::logAction(
            $ticket,
            'comment_added',
            auth()->user(),
            ['is_internal' => $comment->is_internal]
        );

        // Send notifications to mentioned users
        if ($comment->mentioned_user_ids) {
            $comment->notifyMentionedUsers();
        }

        // Send reply notification to customer if external comment. Reuses the
        // seeded `helpdesk.ticket_reply` Mailer template (same as the TicketItem
        // path listener) so the design stays editable from the Mailer admin.
        if (! $comment->is_internal && $ticket->customer?->email) {
            [$subject, $content] = TicketMailRenderer::render(
                'helpdesk.ticket_reply',
                [
                    'CUSTOMER_NAME' => $ticket->customer->name ?? 'Cliente',
                    'TICKET_NUMBER' => $ticket->ticket_number,
                    'SUBJECT' => $ticket->subject,
                    'AGENT_NAME' => auth()->user()?->name ?? 'Soporte',
                    'COMPANY_NAME' => config('app.name', 'Soporte'),
                ],
                'Nueva respuesta en tu ticket #'.$ticket->ticket_number,
            );

            Mail::to($ticket->customer->email, $ticket->customer->name)
                ->queue(new TicketReplyMail($ticket, $subject, $content));

            // Registrar en TicketMail para trazabilidad — esta ruta (comentario
            // externo) enviaba el correo sin dejar rastro en la bandeja de
            // "Emails enviados", a diferencia de la vía TicketItem/MessageAdded
            // (ver SendCustomerReplyNotification), que sí lo hace.
            TicketMail::create([
                'ticket_id' => $ticket->id,
                'ticket_comment_id' => $comment->id,
                'user_id' => auth()->id(),
                'direction' => 'outbound',
                'from' => config('mail.from.address'),
                'to' => $ticket->customer->email,
                'subject' => $subject,
                'body_html' => $content,
                'body_text' => strip_tags($content),
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        return response()->json($comment->load(['user', 'author']), 201);
    }

    /**
     * Display the specified comment.
     */
    public function show(Ticket $ticket, TicketComment $comment): JsonResponse
    {
        $this->authorize('view', $ticket);

        if ($comment->ticket_id !== $ticket->id) {
            abort(404);
        }

        return response()->json($comment->load(['user', 'author', 'editor']));
    }

    /**
     * Update the specified comment in storage.
     */
    public function update(
        UpdateTicketCommentRequest $request,
        Ticket $ticket,
        TicketComment $comment
    ): JsonResponse {
        if ($comment->ticket_id !== $ticket->id) {
            abort(404);
        }

        $this->authorize('update', $comment);

        $oldBody = $comment->body;
        $oldHtmlBody = $comment->html_body;

        $comment->update([
            'body' => $request->input('body') ?? $comment->body,
            'html_body' => $request->input('html_body') ?? $comment->html_body,
            'is_internal' => $request->boolean('is_internal', $comment->is_internal),
            'attachment_urls' => $request->input('attachment_urls') ?? $comment->attachment_urls,
            'edited_by' => auth()->id(),
            'edited_at' => now(),
            'edit_reason' => $request->input('edit_reason'),
        ]);

        // Log history
        TicketHistory::logAction(
            $ticket,
            'comment_edited',
            auth()->user(),
            [
                'comment_id' => $comment->id,
                'reason' => $request->input('edit_reason'),
                'old_body_preview' => substr($oldBody, 0, 100),
            ]
        );

        return response()->json($comment->load(['user', 'author', 'editor']));
    }

    /**
     * Remove the specified comment from storage (soft delete).
     */
    public function destroy(Ticket $ticket, TicketComment $comment): JsonResponse
    {
        if ($comment->ticket_id !== $ticket->id) {
            abort(404);
        }

        $this->authorize('delete', $comment);

        $comment->delete();

        // Log history
        TicketHistory::logAction(
            $ticket,
            'comment_deleted',
            auth()->user(),
            ['comment_id' => $comment->id]
        );

        return response()->json(['message' => 'Comentario eliminado exitosamente']);
    }

    /**
     * Restore a soft-deleted comment.
     */
    public function restore(Ticket $ticket, TicketComment $comment): JsonResponse
    {
        if ($comment->ticket_id !== $ticket->id) {
            abort(404);
        }

        $this->authorize('restore', $comment);

        $comment->restore();

        // Log history
        TicketHistory::logAction(
            $ticket,
            'comment_restored',
            auth()->user(),
            ['comment_id' => $comment->id]
        );

        return response()->json($comment->load(['user', 'author', 'editor']));
    }
}
