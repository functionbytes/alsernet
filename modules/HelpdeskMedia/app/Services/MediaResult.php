<?php

namespace Modules\HelpdeskMedia\Services;

/**
 * Resultado de pasar un fichero por el pipeline. Los adaptadores
 * (conversación/ticket) lo traducen a su modelo.
 */
final class MediaResult
{
    public string $scan = ScanResult::SKIPPED;

    public ?string $scanSignature = null;

    public bool $quarantined = false;

    /** infected | scan_unavailable */
    public ?string $quarantineReason = null;

    /** Copia privada (cuarentena u original archivado) del fichero. */
    public ?string $privateDisk = null;

    public ?string $privatePath = null;

    /** El fichero (path/mime/tamaño) cambió respecto al original. */
    public bool $replaced = false;

    public string $finalPath;

    public string $finalMime;

    public int $finalBytes;

    public ?string $convertedFrom = null;

    public ?int $width = null;

    public ?int $height = null;

    public ?string $transcript = null;

    public ?string $transcriptLang = null;

    public ?string $transcriptModel = null;

    /** Motivo legible cuando algo se omitió (animated_gif, heic_unsupported…). */
    public ?string $note = null;

    /** Original a borrar cuando ya está persistido el cambio (null = nada). */
    public ?string $obsoletePath = null;

    public function __construct(
        public readonly string $kind,
        public readonly string $disk,
        public readonly string $originalPath,
        public readonly string $originalMime,
        public readonly int $originalBytes,
    ) {
        $this->finalPath = $originalPath;
        $this->finalMime = $originalMime;
        $this->finalBytes = $originalBytes;
    }

    /**
     * Estructura de metadata.media[<url>] (conversaciones) y media_meta (tickets).
     * No incluye rutas internas de cuarentena.
     *
     * @return array<string, mixed>
     */
    public function toMeta(): array
    {
        return array_filter([
            'kind' => $this->kind,
            'scan' => $this->scan,
            'scan_signature' => $this->scanSignature,
            'quarantined' => $this->quarantined ?: null,
            'quarantine_reason' => $this->quarantineReason,
            'converted_from' => $this->convertedFrom,
            'original_bytes' => $this->originalBytes,
            'final_bytes' => $this->finalBytes,
            'width' => $this->width,
            'height' => $this->height,
            'transcript' => $this->transcript,
            'transcript_lang' => $this->transcriptLang,
            'transcript_model' => $this->transcriptModel,
            'note' => $this->note,
            'processed_at' => now()->toIso8601String(),
        ], static fn ($value): bool => $value !== null);
    }
}
