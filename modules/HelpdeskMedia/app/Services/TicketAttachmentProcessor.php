<?php

namespace Modules\HelpdeskMedia\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\HelpdeskTickets\Models\TicketAttachment;

/**
 * Procesa un TicketAttachment y deja scan_status, scan_signature, transcript,
 * media_meta y processed_at en su fila. Escribe por query builder: no dispara
 * eventos (ni este observer) y no depende de $fillable del modelo.
 */
class TicketAttachmentProcessor
{
    private static ?bool $columnsReady = null;

    public function __construct(
        private readonly MediaPipeline $pipeline,
        private readonly AttachmentLocator $locator,
    ) {}

    public static function columnsReady(): bool
    {
        return self::$columnsReady ??= Schema::connection('helpdesk')->hasColumn('helpdesk_ticket_attachments', 'processed_at');
    }

    public static function isPending(TicketAttachment $attachment): bool
    {
        return $attachment->getAttribute('processed_at') === null;
    }

    /**
     * @return bool true si se procesó
     *
     * @throws TransientMediaException
     */
    public function process(TicketAttachment $attachment, bool $force = false): bool
    {
        if (! $force && ! self::isPending($attachment)) {
            return false;
        }

        $file = $this->locator->locateTicketPath($attachment->path);

        if ($file === null) {
            Log::warning('HelpdeskMedia: ticket attachment file not found', ['attachment_id' => $attachment->id, 'path' => $attachment->path]);

            return false;
        }

        try {
            $result = $this->pipeline->process($file, $attachment->mime_type);
        } finally {
            $file->release();
        }

        $changes = [
            'scan_status' => $result->scan,
            'scan_signature' => $result->scanSignature,
            'transcript' => $result->transcript,
            'media_meta' => json_encode($result->toMeta()),
            'processed_at' => now(),
        ];

        if ($result->replaced && ! $result->quarantined) {
            $changes['path'] = $result->finalPath;
            $changes['mime_type'] = $result->finalMime;
            $changes['size'] = $result->finalBytes;
            $changes = array_merge($changes, $this->renamed($attachment, $result->finalPath));
        }

        if ($result->quarantined) {
            // El fichero ya no está en su disco: la descarga da 404. La ruta se
            // conserva como rastro; la cuarentena queda en el log.
            $changes['scan_status'] = $result->scan === ScanResult::INFECTED ? ScanResult::INFECTED : 'blocked';
        }

        TicketAttachment::query()->whereKey($attachment->id)->update($changes);
        $this->pipeline->finalize($result);

        Log::info('HelpdeskMedia: ticket attachment processed', [
            'attachment_id' => $attachment->id,
            'kind' => $result->kind,
            'scan' => $result->scan,
            'quarantined' => $result->quarantined,
            'converted_from' => $result->convertedFrom,
            'original_bytes' => $result->originalBytes,
            'final_bytes' => $result->finalBytes,
            'transcribed' => $result->transcript !== null,
            'note' => $result->note,
        ]);

        return true;
    }

    /**
     * @return array<string, string>
     */
    private function renamed(TicketAttachment $attachment, string $finalPath): array
    {
        $extension = pathinfo($finalPath, PATHINFO_EXTENSION);
        $changes = [];

        foreach (['filename', 'original_filename'] as $column) {
            $name = (string) $attachment->getAttribute($column);

            if ($name !== '') {
                $changes[$column] = pathinfo($name, PATHINFO_FILENAME).'.'.$extension;
            }
        }

        return $changes;
    }
}
