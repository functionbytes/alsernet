<?php

namespace Modules\HelpdeskMedia\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Fichero de un disco de Laravel con acceso a ruta local (copia temporal si el
 * disco no es local) y a su tipo MIME real (finfo, no la extensión).
 */
final class StoredFile
{
    private ?string $tempPath = null;

    public function __construct(
        public readonly string $disk,
        public readonly string $path,
    ) {}

    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    public function size(): int
    {
        return (int) Storage::disk($this->disk)->size($this->path);
    }

    /**
     * @return resource|null
     */
    public function stream()
    {
        return Storage::disk($this->disk)->readStream($this->path);
    }

    public function localPath(): string
    {
        if (config("filesystems.disks.{$this->disk}.driver") === 'local') {
            return Storage::disk($this->disk)->path($this->path);
        }

        if ($this->tempPath !== null) {
            return $this->tempPath;
        }

        $temp = tempnam(sys_get_temp_dir(), 'hdmedia_');
        $source = $this->stream();
        $target = fopen($temp, 'wb');

        if ($source === null || $target === false) {
            throw new \RuntimeException("No se puede leer {$this->disk}:{$this->path}");
        }

        stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);

        return $this->tempPath = $temp;
    }

    public function realMime(): ?string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($this->localPath());

        return is_string($mime) ? strtolower($mime) : null;
    }

    public function release(): void
    {
        if ($this->tempPath !== null && is_file($this->tempPath)) {
            @unlink($this->tempPath);
        }

        $this->tempPath = null;
    }
}
