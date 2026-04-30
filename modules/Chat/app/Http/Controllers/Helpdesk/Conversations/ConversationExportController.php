<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Conversations;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Chat\Exports\ConversationsExport;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Mail\ConversationTranscript;
use Modules\Chat\Models\Conversations\Conversation;
use Symfony\Component\HttpFoundation\BinaryFileDownload;

class ConversationExportController extends Controller
{
    /**
     * Export conversation to PDF.
     */
    public function exportToPdf(Conversation $conversation): BinaryFileDownload
    {
        $conversation->load([
            'customer',
            'inbox.channel',
            'assignee',
            'team',
            'messages.sender',
        ]);

        $pdf = Pdf::loadView('Chat::chats.conversation.pdf', [
            'conversation' => $conversation,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        $filename = sprintf(
            'conversacion_%d_%s.pdf',
            $conversation->id,
            now()->format('Ymd_His')
        );

        return $pdf->download($filename);
    }

    /**
     * Show print-optimized view for conversation.
     */
    public function printView(Conversation $conversation): View
    {
        $conversation->load([
            'customer',
            'inbox.channel',
            'assignee',
            'team',
            'messages.sender',
        ]);

        return view('Chat::chats.conversation.print', compact('conversation'));
    }

    /**
     * Export conversation to Excel/CSV.
     */
    public function exportToExcel(Request $request): BinaryFileDownload
    {
        $accountId = $request->user()->account_id ?? abort(403, 'No account assigned');

        $filters = $request->only(['status', 'inbox_id', 'assignee_id', 'team_id', 'created_after', 'created_before']);
        $format = $request->query('format', 'xlsx');

        $filename = sprintf(
            'conversaciones_%s.%s',
            now()->format('Ymd_His'),
            $format
        );

        return Excel::download(
            new ConversationsExport($accountId, $filters),
            $filename
        );
    }

    /**
     * Email conversation transcript.
     */
    public function emailTranscript(Request $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string|max:500',
        ]);

        $conversation->load([
            'customer',
            'inbox.channel',
            'assignee',
            'team',
            'messages.sender',
        ]);

        $pdf = Pdf::loadView('Chat::chats.conversation.pdf', [
            'conversation' => $conversation,
        ]);

        $pdf->setPaper('A4', 'portrait');

        \Mail::to($validated['email'])->send(
            new ConversationTranscript(
                $conversation,
                $pdf->output(),
                $validated['message'] ?? null
            )
        );

        return back()->with('success', '¡Transcripción enviada por email exitosamente!');
    }
}
