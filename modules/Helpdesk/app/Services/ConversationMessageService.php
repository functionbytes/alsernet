<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Http\UploadedFile;
use Modules\Helpdesk\Events\ConversationClosed;
use Modules\Helpdesk\Events\ConversationItemCreated;
use Modules\Helpdesk\Events\InboxItemChanged;
use Modules\Helpdesk\Events\MentionDetected;
use Modules\Helpdesk\Events\MessageReceived;
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

        $metadata = [];
        if (! empty($data['reply_to_id'])) {
            $metadata['reply_to_id'] = (int) $data['reply_to_id'];
        }

        $authorId = auth()->id();

        // Resolver menciones en el body antes de crear el item
        $mentions = $this->mentionParser->resolve($data['body'] ?? '', $authorId, $conversation);
        if ($mentions['users']->isNotEmpty() || $mentions['teams']->isNotEmpty()) {
            $metadata['mentions'] = [
                'handles' => $mentions['handles'],
                'user_ids' => $mentions['users']->pluck('id')->all(),
                'team_ids' => $mentions['teams']->pluck('id')->all(),
            ];
        }

        $item = $conversation->items()->create([
            'user_id' => $authorId,
            'type' => 'message',
            'body' => $data['body'],
            'html_body' => nl2br(e($data['body'])),
            'is_internal' => $data['is_internal'] ?? false,
            'attachment_urls' => ! empty($attachmentUrls) ? $attachmentUrls : null,
            'metadata' => ! empty($metadata) ? $metadata : null,
        ]);

        if (empty($data['is_internal']) && $this->outbound->supports($conversation)) {
            // Envío externo: limpiamos los @handles para que el cliente no vea identificadores internos
            $externalBody = $this->mentionParser->stripForExternal(strip_tags($data['body'] ?? ''));
            $externalMessageId = filled($externalBody)
                ? $this->outbound->sendReply($conversation, $externalBody)
                : null;

            // Enviar cada attachment como mensaje independiente al canal (Meta
            // exige uno por petición). Convertimos URL relativa a absoluta con
            // dominio público para que Meta pueda fetcharla.
            foreach ($attachmentUrls as $att) {
                $absUrl = $this->absoluteUrl((string) ($att['url'] ?? ''));
                if (blank($absUrl)) {
                    continue;
                }

                $attMessageId = $this->outbound->sendAttachment(
                    $conversation,
                    (string) ($att['type'] ?? 'file'),
                    $absUrl,
                    null,
                    $att['name'] ?? null,
                );

                $externalMessageId = $externalMessageId ?: $attMessageId;
            }

            if ($externalMessageId) {
                $item->update(['metadata' => array_merge($item->metadata ?? [], [
                    'outbound_message_id' => $externalMessageId,
                    'sent_via' => $conversation->channel,
                ])]);
            }
        }

        // Disparar evento por cada usuario único mencionado (excluyendo al autor)
        $author = auth()->user();
        $authorName = trim(($author?->firstname ?? '').' '.($author?->lastname ?? '')) ?: ($author?->email ?? 'Alguien');
        foreach ($mentions['users'] as $mentionedUser) {
            event(new MentionDetected($conversation, $item, $mentionedUser, $authorName));
        }

        event(new ConversationItemCreated($item));

        if (! $item->is_internal) {
            broadcast(new MessageReceived($conversation, $item));
        }

        $this->dispatchInboxChanged($conversation, 'message_added');

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

    /**
     * Notify all agents subscribed to helpdesk.user.{id} that the inbox changed.
     * Broadcasts to the assignee (if any) and to the authenticated user.
     */
    private function dispatchInboxChanged(Conversation $conversation, string $changeType): void
    {
        $userIds = array_filter(array_unique([
            $conversation->assignee_id,
            auth()->id(),
        ]));

        foreach ($userIds as $userId) {
            event(new InboxItemChanged($conversation->id, $userId, $changeType));
        }
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
