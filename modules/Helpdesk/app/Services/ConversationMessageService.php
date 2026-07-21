<?php

namespace Modules\Helpdesk\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Events\MentionDetected;
use Modules\Helpdesk\Jobs\BroadcastOutboundMessageJob;
use Modules\Helpdesk\Jobs\SendOutboundMessageJob;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;

class ConversationMessageService
{
    public function __construct(
        private OutboundMessageService $outbound,
        private MentionParser $mentionParser,
    ) {}

    /**
     * Store a new message in a conversation.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: ConversationItem, 1: string}
     */
    public function store(Conversation $conversation, array $data): array
    {
        $attachmentUrls = $this->processAttachments($data['attachments'] ?? []);

        $metadata = $data['metadata'] ?? [];
        if (! empty($data['reply_to_id'])) {
            $metadata['reply_to_id'] = (int) $data['reply_to_id'];
        }

        // Callers running outside an HTTP request (automation rules, scheduled
        // jobs) have no authenticated agent — they pass 'user_id' explicitly so
        // the item is still attributed to a real agent instead of landing with
        // user_id=null, which the UI renders identically to a CUSTOMER message
        // (isFromCustomer() checks author_id, but the thread/list templates only
        // check "is there a user_id?" — no user_id reads as incoming either way).
        $authorId = $data['user_id'] ?? auth()->id();

        // Resolver menciones en el body antes de crear el item
        $mentions = $this->mentionParser->resolve($data['body'] ?? '', $authorId, $conversation);
        if ($mentions['users']->isNotEmpty() || $mentions['teams']->isNotEmpty()) {
            $metadata['mentions'] = [
                'handles' => $mentions['handles'],
                'user_ids' => $mentions['users']->pluck('id')->all(),
                'team_ids' => $mentions['teams']->pluck('id')->all(),
            ];
        }

        $scheduledAt = $data['scheduled_at'] ?? null;
        $isScheduled = filled($scheduledAt) && Carbon::parse($scheduledAt)->greaterThan(now());

        $item = $conversation->items()->create([
            'user_id' => $authorId,
            'type' => 'message',
            'body' => $data['body'],
            'html_body' => nl2br(e($data['body'])),
            'is_internal' => $data['is_internal'] ?? false,
            'attachment_urls' => ! empty($attachmentUrls) ? $attachmentUrls : null,
            'metadata' => ! empty($metadata) ? $metadata : null,
            'scheduled_at' => $scheduledAt,
            'scheduled_by' => $isScheduled ? $authorId : null,
        ]);

        // Si es un envío programado a futuro, no enviamos ni disparamos eventos ahora.
        if ($isScheduled) {
            return [$item, 'Mensaje programado.'];
        }

        if (empty($data['is_internal']) && $this->outbound->supports($conversation)) {
            // Envío externo encolado: las llamadas Graph/WhatsApp (timeout 15s +
            // reintentos) bloquearían el worker FPM. Resolvemos aquí el body
            // (limpiando @handles internos) y las URL absolutas, y delegamos la I/O
            // al job, que correlaciona el id externo en metadata al volver.
            $externalBody = $this->mentionParser->stripForExternal(strip_tags($data['body'] ?? ''));

            $outboundAttachments = [];
            foreach ($attachmentUrls as $att) {
                $absUrl = $this->absoluteUrl((string) ($att['url'] ?? ''));
                if (blank($absUrl)) {
                    continue;
                }

                $outboundAttachments[] = [
                    'type' => (string) ($att['type'] ?? 'file'),
                    'url' => $absUrl,
                    'name' => $att['name'] ?? null,
                ];
            }

            // afterCommit: cuando el mensaje se crea dentro de una transacción
            // (p.ej. acciones de automatización/macro), el job solo se encola
            // si esta confirma — si hay rollback el item no existirá. Fuera de
            // una transacción se despacha inmediatamente (no-op).
            SendOutboundMessageJob::dispatch(
                $conversation->id,
                $item->id,
                filled($externalBody) ? $externalBody : null,
                $outboundAttachments,
                'metadata',
            )->afterCommit();
        }

        // Disparar evento por cada usuario único mencionado (excluyendo al autor)
        $author = auth()->user();
        $authorName = trim(($author?->firstname ?? '').' '.($author?->lastname ?? '')) ?: ($author?->email ?? 'Alguien');
        foreach ($mentions['users'] as $mentionedUser) {
            event(new MentionDetected($conversation, $item, $mentionedUser, $authorName));
        }

        // Broadcasting is off the request thread: the sending agent already
        // sees their own message via optimistic UI, so making them wait on two
        // Reverb round-trips before confirming "sent" only slowed them down.
        BroadcastOutboundMessageJob::dispatch($conversation->id, $item->id)->afterCommit();

        $this->updateConversationTimestamps($conversation);

        $shouldClose = ($data['action'] ?? null) === 'send_and_close';

        if ($shouldClose) {
            $conversation->close();
            ConversationClosed::dispatch($conversation);
        }

        $successMessage = $shouldClose
            ? __('helpdesk::helpdesk.messages.conversation_message_sent_and_closed')
            : __('helpdesk::helpdesk.messages.conversation_message_sent');

        return [$item, $successMessage];
    }

    /**
     * @param  UploadedFile[]  $files
     * @return array<int, array{name: string, url: string, size: int, mime_type: string, type: string, path: string}>
     */
    private function processAttachments(array $files): array
    {
        $urls = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('helpdesk/attachments', 'public');
            $mime = $file->getMimeType() ?? 'application/octet-stream';
            $urls[] = [
                'name' => $file->getClientOriginalName(),
                'url' => asset('storage/'.$path),
                'size' => $file->getSize(),
                'mime_type' => $mime,
                'mime' => $mime,        // backwards-compat with old readers
                'type' => $this->mediaTypeFromMime($mime),
                'path' => $path,
            ];
        }

        return $urls;
    }

    private function mediaTypeFromMime(string $mime): string
    {
        $primary = strtolower(explode('/', explode(';', $mime, 2)[0], 2)[0] ?? '');

        return match ($primary) {
            'image' => 'image',
            'audio' => 'audio',
            'video' => 'video',
            default => 'document',
        };
    }

    /**
     * Convert a relative URL (e.g. "/storage/...") into an absolute https URL
     * that Meta or external services can fetch from outside the local network.
     */
    private function absoluteUrl(string $url): string
    {
        if ($url === '' || preg_match('#^https?://#i', $url)) {
            // Replace local-only host with the public tunnel host so Meta can reach it.
            $base = rtrim(config('helpdesk.public_url') ?? config('app.url'), '/');
            $appUrl = rtrim((string) config('app.url'), '/');

            if ($appUrl && $base && $base !== $appUrl && str_starts_with($url, $appUrl.'/')) {
                return $base.substr($url, strlen($appUrl));
            }

            return $url;
        }

        $base = rtrim(config('helpdesk.public_url') ?? config('app.url'), '/');

        return $base.'/'.ltrim($url, '/');
    }

    private function updateConversationTimestamps(Conversation $conversation): void
    {
        $data = ['last_message_at' => now()];
        if (! $conversation->first_response_at) {
            $data['first_response_at'] = now();
        }
        $conversation->update($data);
    }
}
