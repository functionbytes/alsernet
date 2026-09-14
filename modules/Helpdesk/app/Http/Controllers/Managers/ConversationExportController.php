<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Http\Requests\Exports\ExportConversationTranscriptRequest;
use Modules\Helpdesk\Mail\ConversationExportMail;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class ConversationExportController extends Controller
{
    /**
     * GET /panel/helpdesk/exports/conversations
     * Export a conversation transcript as PDF, CSV, JSON or EML (#57 ve-export).
     */
    public function export(ExportConversationTranscriptRequest $request): Response
    {
        $file = $this->buildFile($request);

        return response($file['content'], 200, [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => 'attachment; filename="'.$file['filename'].'"',
        ]);
    }

    /**
     * POST /panel/helpdesk/exports/conversation-transcript/email
     * Same export, delivered as an attachment to the requesting agent's own
     * email instead of downloaded — there is no recipient picker in the
     * modal, so "send by email" means "email me a copy".
     */
    public function sendByEmail(ExportConversationTranscriptRequest $request): JsonResponse
    {
        $file = $this->buildFile($request);
        $conversation = Conversation::query()->findOrFail($request->validated('conversation_id'));

        Mail::to($request->user())->send(new ConversationExportMail(
            $conversation,
            $file['content'],
            $file['filename'],
            $file['mime'],
        ));

        return response()->json([
            'success' => true,
            'message' => 'Te enviamos el archivo a '.$request->user()->email.'.',
        ]);
    }

    /**
     * @return array{content: string, filename: string, mime: string}
     */
    private function buildFile(ExportConversationTranscriptRequest $request): array
    {
        $validated = $request->validated();

        $conversation = Conversation::query()->with('customer')->findOrFail($validated['conversation_id']);
        $this->authorize('view', $conversation);

        $includeNotes = $request->boolean('include_notes');
        $includeMeta = $request->boolean('include_meta');
        $includeAttachments = $request->boolean('include_attachments');
        $includeHeader = $request->has('include_header') ? $request->boolean('include_header') : true;

        $items = ConversationItem::query()
            ->where('conversation_id', $conversation->id)
            ->when(! $includeNotes, fn ($q) => $q->where('is_internal', false))
            ->orderBy('created_at')
            ->get();

        $rows = $items->map(fn (ConversationItem $item) => [
            'date' => $item->created_at?->toIso8601String(),
            'sender' => $item->user_id ? 'Agente' : ($conversation->customer?->name ?? 'Cliente'),
            'internal' => (bool) $item->is_internal,
            'type' => $item->type,
            'body' => trim(preg_replace('/\s+/', ' ', strip_tags((string) $item->body))),
            'attachments' => $includeAttachments ? (array) ($item->attachment_urls ?? []) : [],
            'metadata' => $includeMeta ? (array) ($item->metadata ?? []) : [],
        ])->all();

        $basename = 'conversacion-'.$conversation->id.'-'.now()->format('Ymd-His');

        return match ($validated['format']) {
            'json' => $this->buildJson($conversation, $rows, $includeMeta, $includeHeader, $basename),
            'csv' => $this->buildCsv($conversation, $rows, $includeMeta, $includeAttachments, $includeHeader, $basename),
            'eml' => $this->buildEml($conversation, $rows, $includeMeta, $includeAttachments, $includeHeader, $basename),
            default => $this->buildPdf($conversation, $rows, $includeMeta, $includeAttachments, $includeHeader, $basename),
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{content: string, filename: string, mime: string}
     */
    private function buildJson(Conversation $conversation, array $rows, bool $includeMeta, bool $includeHeader, string $basename): array
    {
        $payload = [
            'conversation' => [
                'id' => $conversation->id,
                'subject' => $conversation->subject,
                'channel' => $conversation->channel,
                'customer' => $includeHeader ? $conversation->customer?->name : null,
                'exported_at' => now()->toIso8601String(),
            ],
            'messages' => $rows,
        ];

        if (! $includeMeta) {
            foreach ($payload['messages'] as &$m) {
                unset($m['metadata']);
            }
        }

        return [
            'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'filename' => $basename.'.json',
            'mime' => 'application/json',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{content: string, filename: string, mime: string}
     */
    private function buildCsv(Conversation $conversation, array $rows, bool $includeMeta, bool $includeAttachments, bool $includeHeader, string $basename): array
    {
        $headers = ['Fecha', 'Remitente', 'Nota interna', 'Tipo', 'Mensaje'];
        if ($includeAttachments) {
            $headers[] = 'Adjuntos';
        }
        if ($includeMeta) {
            $headers[] = 'Metadatos';
        }

        $out = fopen('php://temp', 'r+');
        fprintf($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel

        if ($includeHeader) {
            fputcsv($out, ['Cliente', $conversation->customer?->name ?: '—']);
            fputcsv($out, ['Canal', $conversation->channel]);
            fputcsv($out, []);
        }

        fputcsv($out, $headers);
        foreach ($rows as $r) {
            $line = [
                $r['date'],
                $r['sender'],
                $r['internal'] ? 'Sí' : 'No',
                $r['type'],
                $r['body'],
            ];
            if ($includeAttachments) {
                $line[] = implode(' | ', $r['attachments']);
            }
            if ($includeMeta) {
                $line[] = json_encode($r['metadata'], JSON_UNESCAPED_UNICODE);
            }
            fputcsv($out, $line);
        }

        rewind($out);
        $content = stream_get_contents($out);
        fclose($out);

        return [
            'content' => $content,
            'filename' => $basename.'.csv',
            'mime' => 'text/csv; charset=UTF-8',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{content: string, filename: string, mime: string}
     */
    private function buildPdf(Conversation $conversation, array $rows, bool $includeMeta, bool $includeAttachments, bool $includeHeader, string $basename): array
    {
        $pdf = Pdf::loadView('helpdesk::exports.conversation', [
            'conversation' => $conversation,
            'rows' => $rows,
            'includeMeta' => $includeMeta,
            'includeAttachments' => $includeAttachments,
            'includeHeader' => $includeHeader,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4');

        return [
            'content' => $pdf->output(),
            'filename' => $basename.'.pdf',
            'mime' => 'application/pdf',
        ];
    }

    /**
     * Builds a standalone .eml (RFC822) file with the transcript as its body —
     * reuses the same HTML the PDF export renders. The customer's address (or
     * a local placeholder when missing) satisfies the To: header; this file is
     * never actually sent through a transport, only saved/attached.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{content: string, filename: string, mime: string}
     */
    private function buildEml(Conversation $conversation, array $rows, bool $includeMeta, bool $includeAttachments, bool $includeHeader, string $basename): array
    {
        $html = view('helpdesk::exports.conversation', [
            'conversation' => $conversation,
            'rows' => $rows,
            'includeMeta' => $includeMeta,
            'includeAttachments' => $includeAttachments,
            'includeHeader' => $includeHeader,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->render();

        $text = collect($rows)->map(function (array $r) {
            $when = $r['date'] ? Carbon::parse($r['date'])->format('d/m/Y H:i') : '';

            return "[{$when}] {$r['sender']}: {$r['body']}";
        })->implode("\n");

        $customerEmail = $conversation->customer?->email ?: 'sin-email@conversacion.local';

        $email = (new Email)
            ->from(new Address(config('mail.from.address', 'noreply@localhost'), config('mail.from.name', config('app.name'))))
            ->to($customerEmail)
            ->subject($conversation->subject ?: 'Conversación #'.$conversation->id)
            ->html($html)
            ->text($text ?: 'Sin mensajes para exportar.');

        return [
            'content' => $email->toString(),
            'filename' => $basename.'.eml',
            'mime' => 'message/rfc822',
        ];
    }
}
