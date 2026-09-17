<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

/**
 * Common malware boundary for every Helpdesk upload path.
 *
 * FormRequests validate the extension and binary MIME signature. This service
 * performs the final ClamAV check immediately before a file is persisted, and
 * deliberately fails closed when scanning has been enabled but the scanner is
 * unavailable.
 */
class AttachmentSecurityService
{
    public function __construct(private readonly HelpdeskSettings $settings) {}

    public function isEnabled(): bool
    {
        return $this->settings->virusScanEnabled();
    }

    public function assertSafe(UploadedFile $file): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if (! $file->isValid() || ! $file->getRealPath()) {
            throw ValidationException::withMessages([
                'attachments' => 'No se pudo leer el archivo adjunto para verificarlo.',
            ]);
        }

        $clamscanPath = (string) config('helpdesk.attachments.virus_scan.clamscan_path', 'clamscan');
        $probe = new Process(['which', $clamscanPath]);
        $probe->setTimeout(10);
        $probe->run();

        if (! $probe->isSuccessful()) {
            Log::critical('ClamAV no está disponible para las subidas de Helpdesk', [
                'path' => $clamscanPath,
                'filename' => $file->getClientOriginalName(),
            ]);

            throw ValidationException::withMessages([
                'attachments' => 'No se puede verificar el archivo adjunto ahora. Inténtalo de nuevo más tarde.',
            ]);
        }

        $this->scanPath($file->getRealPath(), $file->getClientOriginalName(), $clamscanPath);
    }

    /**
     * Scan an attachment that is already stored locally, such as an inbound
     * email or a legacy attachment being forwarded.
     */
    public function assertSafeStored(string $disk, string $path, string $filename = 'attachment'): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $storage = Storage::disk($disk);
        if (! $storage->exists($path)) {
            throw new \RuntimeException('El adjunto no existe para poder escanearlo.');
        }

        try {
            $localPath = $storage->path($path);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('El disco de adjuntos no permite escaneo local.', 0, $exception);
        }

        $this->scanPath(
            $localPath,
            $filename,
            (string) config('helpdesk.attachments.virus_scan.clamscan_path', 'clamscan'),
        );
    }

    private function scanPath(string $path, string $filename, string $clamscanPath): void
    {
        $process = new Process([$clamscanPath, '--no-summary', '--infected', $path]);
        $process->setTimeout(max(10, (int) config('helpdesk.attachments.virus_scan.timeout', 300)));
        $process->run();

        if ($process->getExitCode() === 1) {
            Log::critical('Archivo infectado bloqueado en Helpdesk', [
                'filename' => $filename,
                'output' => trim($process->getOutput().' '.$process->getErrorOutput()),
            ]);

            throw ValidationException::withMessages([
                'attachments' => 'El archivo adjunto fue bloqueado porque contiene una amenaza.',
            ]);
        }

        if (! $process->isSuccessful()) {
            Log::error('ClamAV no pudo completar el escaneo de un adjunto de Helpdesk', [
                'filename' => $filename,
                'exit_code' => $process->getExitCode(),
                'error' => trim($process->getErrorOutput()),
            ]);

            throw ValidationException::withMessages([
                'attachments' => 'No se pudo verificar el archivo adjunto. Inténtalo de nuevo más tarde.',
            ]);
        }
    }
}
