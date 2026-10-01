<?php

namespace Modules\HelpdeskMedia\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskMedia\Events\ConversationItemMediaProcessed;
use Modules\HelpdeskMedia\Support\StoredFile;

/**
 * Procesa los attachment_urls de un ConversationItem y deja el resultado en
 * metadata.media[<url final>]. Persiste fichero a fichero (con saveQuietly, sin
 * volver a disparar el observer): si falla el tercero, los dos primeros ya
 * están en BD apuntando al fichero nuevo.
 */
class ConversationMediaProcessor
{
    public const PLACEHOLDER_ROUTE = 'helpdesk-media.blocked';

    public function __construct(
        private readonly MediaPipeline $pipeline,
        private readonly AttachmentLocator $locator,
    ) {}

    /**
     * ¿Hay algún adjunto sin entrada en metadata.media? (barato, sin tocar disco)
     */
    public static function hasPending(ConversationItem $item): bool
    {
        $metadata = is_array($item->metadata) ? $item->metadata : [];
        $media = is_array($metadata['media'] ?? null) ? $metadata['media'] : [];

        foreach (self::rawAttachments($item) as $entry) {
            $url = self::urlOf($entry);

            if ($url !== null && ! isset($media[$url])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int adjuntos procesados en esta pasada
     *
     * @throws TransientMediaException si algún adjunto debe reintentarse
     */
    public function process(ConversationItem $item, bool $force = false): int
    {
        $metadata = is_array($item->metadata) ? $item->metadata : [];
        $media = is_array($metadata['media'] ?? null) ? $metadata['media'] : [];

        $processed = 0;
        $transient = null;
        $failure = null;

        foreach (self::rawAttachments($item) as $index => $entry) {
            $url = self::urlOf($entry);

            if ($url === null || (! $force && isset($media[$url]))) {
                continue;
            }

            $file = $this->locator->locateUrl($url);

            if ($file === null) {
                continue;
            }

            try {
                $result = $this->pipeline->process($file, self::declaredMime($entry));
            } catch (TransientMediaException $e) {
                $transient = $e;

                continue;
            } catch (\Throwable $e) {
                Log::error('HelpdeskMedia: conversation attachment failed', [
                    'item_id' => $item->id,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
                $failure = $e;

                continue;
            } finally {
                $file->release();
            }

            if ($this->persist($item->id, $index, $url, $result, $file) === null) {
                continue;
            }

            $this->pipeline->finalize($result);
            $processed++;

            Log::info('HelpdeskMedia: conversation attachment processed', [
                'item_id' => $item->id,
                'kind' => $result->kind,
                'scan' => $result->scan,
                'quarantined' => $result->quarantined,
                'converted_from' => $result->convertedFrom,
                'original_bytes' => $result->originalBytes,
                'final_bytes' => $result->finalBytes,
                'transcribed' => $result->transcript !== null,
                'note' => $result->note,
            ]);

            if ($result->quarantined) {
                $this->addBlockedNote($item, $result);
            }
        }

        if ($processed > 0) {
            event(new ConversationItemMediaProcessed($item->fresh()));
        }

        if ($transient !== null || $failure !== null) {
            throw $transient ?? $failure;
        }

        return $processed;
    }

    /**
     * @return string|null URL final, o null si el item cambió mientras tanto
     */
    private function persist(int $itemId, int $index, string $oldUrl, MediaResult $result, StoredFile $file): ?string
    {
        return DB::connection('helpdesk')->transaction(function () use ($itemId, $index, $oldUrl, $result, $file): ?string {
            $item = ConversationItem::withTrashed()->lockForUpdate()->find($itemId);

            if ($item === null) {
                return null;
            }

            $attachments = self::rawAttachments($item);
            $position = $this->findPosition($attachments, $index, $oldUrl);

            if ($position === null) {
                return null;
            }

            $newUrl = $this->finalUrl($oldUrl, $result, $file);
            $attachments[$position] = $this->applyToEntry($attachments[$position], $newUrl, $result);

            $metadata = is_array($item->metadata) ? $item->metadata : [];
            $media = is_array($metadata['media'] ?? null) ? $metadata['media'] : [];
            unset($media[$oldUrl]);
            $media[$newUrl] = $result->toMeta();
            $metadata['media'] = $media;
            $metadata = $this->mirrorInMetadataAttachments($metadata, $oldUrl, $newUrl, $result);

            $item->attachment_urls = $attachments;
            $item->metadata = $metadata;
            $item->saveQuietly();

            return $newUrl;
        });
    }

    /**
     * @param  array<int, mixed>  $attachments
     */
    private function findPosition(array $attachments, int $index, string $url): ?int
    {
        if (isset($attachments[$index]) && self::urlOf($attachments[$index]) === $url) {
            return $index;
        }

        foreach ($attachments as $position => $entry) {
            if (self::urlOf($entry) === $url) {
                return $position;
            }
        }

        return null;
    }

    private function finalUrl(string $oldUrl, MediaResult $result, StoredFile $file): string
    {
        if ($result->quarantined) {
            return route(self::PLACEHOLDER_ROUTE, ['f' => substr(sha1($oldUrl), 0, 12)]);
        }

        if ($result->replaced) {
            return $this->locator->rebuildUrl($oldUrl, $file->disk, $file->path, $result->finalPath);
        }

        return $oldUrl;
    }

    private function applyToEntry(mixed $entry, string $newUrl, MediaResult $result): mixed
    {
        if (! is_array($entry)) {
            return $newUrl;
        }

        return $this->applyToArrayEntry($entry, $newUrl, $result);
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    private function applyToArrayEntry(array $entry, string $newUrl, MediaResult $result): array
    {
        $entry['url'] = $newUrl;

        if ($result->quarantined) {
            unset($entry['path'], $entry['disk']);
            $entry['name'] = '[Bloqueado] '.($entry['name'] ?? 'adjunto');
            $entry['mime_type'] = 'image/svg+xml';
            $entry['size'] = 0;
            $entry['blocked'] = true;

            return $entry;
        }

        if (! $result->replaced) {
            return $entry;
        }

        if (isset($entry['path'])) {
            $entry['path'] = $result->finalPath;
        }

        $entry['mime_type'] = $result->finalMime;
        $entry['size'] = $result->finalBytes;

        if (isset($entry['mime'])) {
            $entry['mime'] = $result->finalMime;
        }

        if (isset($entry['name']) && is_string($entry['name'])) {
            $entry['name'] = pathinfo($entry['name'], PATHINFO_FILENAME).'.'.pathinfo($result->finalPath, PATHINFO_EXTENSION);
        }

        return $entry;
    }

    /**
     * ConversationAttachmentsController duplica los adjuntos en metadata.attachments.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function mirrorInMetadataAttachments(array $metadata, string $oldUrl, string $newUrl, MediaResult $result): array
    {
        if (! is_array($metadata['attachments'] ?? null)) {
            return $metadata;
        }

        foreach ($metadata['attachments'] as $position => $mirrored) {
            if (is_array($mirrored) && ($mirrored['url'] ?? null) === $oldUrl) {
                $metadata['attachments'][$position] = $this->applyToArrayEntry($mirrored, $newUrl, $result);
            }
        }

        return $metadata;
    }

    private function addBlockedNote(ConversationItem $item, MediaResult $result): void
    {
        $body = $result->quarantineReason === 'infected'
            ? 'Adjunto bloqueado por antivirus: '.$result->scanSignature
            : 'Adjunto bloqueado: el antivirus no estaba disponible para analizarlo';

        $note = ConversationItem::query()->create([
            'conversation_id' => $item->conversation_id,
            'user_id' => null,
            'author_id' => null,
            'type' => 'note',
            'body' => $body,
            'is_internal' => true,
            'metadata' => [
                'source' => 'helpdesk-media',
                'blocked_item_id' => $item->id,
                'quarantine_reason' => $result->quarantineReason,
            ],
        ]);

        event(new ConversationMessageCreated($note));
    }

    /**
     * Lista cruda (sin el accesor que reescribe hosts) para no tocar URLs ajenas.
     *
     * @return array<int, mixed>
     */
    private static function rawAttachments(ConversationItem $item): array
    {
        $raw = $item->getAttributes()['attachment_urls'] ?? null;
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

        return is_array($decoded) ? $decoded : [];
    }

    private static function urlOf(mixed $entry): ?string
    {
        $url = is_array($entry) ? ($entry['url'] ?? null) : $entry;

        return is_string($url) && $url !== '' ? $url : null;
    }

    private static function declaredMime(mixed $entry): ?string
    {
        if (! is_array($entry)) {
            return null;
        }

        $mime = $entry['mime_type'] ?? $entry['mime'] ?? null;

        return is_string($mime) && $mime !== '' ? $mime : null;
    }
}
