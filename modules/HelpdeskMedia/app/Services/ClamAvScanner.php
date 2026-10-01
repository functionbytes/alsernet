<?php

namespace Modules\HelpdeskMedia\Services;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskMedia\Support\StoredFile;

/**
 * Cliente mínimo de clamd por TCP con el comando INSTREAM (sin paquetes):
 * "zINSTREAM\0", después trozos [longitud 4 bytes BE][datos] y un trozo final
 * de longitud 0. clamd responde "stream: OK" o "stream: <firma> FOUND".
 */
class ClamAvScanner
{
    public function enabled(): bool
    {
        return (bool) config('helpdeskmedia.clamav.enabled', false);
    }

    public function scan(StoredFile $file): ScanResult
    {
        if (! $this->enabled()) {
            return new ScanResult(ScanResult::SKIPPED, reason: 'disabled');
        }

        $maxBytes = (int) config('helpdeskmedia.clamav.max_mb', 25) * 1048576;

        if ($file->size() > $maxBytes) {
            return new ScanResult(ScanResult::UNAVAILABLE, reason: 'too_large');
        }

        $source = $file->stream();

        if ($source === null) {
            return new ScanResult(ScanResult::UNAVAILABLE, reason: 'unreadable');
        }

        try {
            return $this->instream($source);
        } catch (\Throwable $e) {
            Log::warning('HelpdeskMedia: clamd unavailable', ['error' => $e->getMessage(), 'file' => $file->path]);

            return new ScanResult(ScanResult::UNAVAILABLE, reason: $e->getMessage());
        } finally {
            fclose($source);
        }
    }

    /**
     * @param  resource  $source
     */
    private function instream($source): ScanResult
    {
        $host = (string) config('helpdeskmedia.clamav.host', '127.0.0.1');
        $port = (int) config('helpdeskmedia.clamav.port', 3310);
        $timeout = (int) config('helpdeskmedia.clamav.timeout', 30);
        $chunkBytes = (int) config('helpdeskmedia.clamav.chunk_bytes', 65536);

        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $error,
            (float) config('helpdeskmedia.clamav.connect_timeout', 5),
        );

        if ($socket === false) {
            throw new \RuntimeException("connect failed: {$error} ({$errno})");
        }

        try {
            stream_set_timeout($socket, $timeout);
            $this->write($socket, "zINSTREAM\0");

            while (! feof($source)) {
                $chunk = fread($source, $chunkBytes);

                if ($chunk === false || $chunk === '') {
                    break;
                }

                $this->write($socket, pack('N', strlen($chunk)).$chunk);
            }

            $this->write($socket, pack('N', 0));

            return $this->parse($this->readResponse($socket));
        } finally {
            fclose($socket);
        }
    }

    /**
     * @param  resource  $socket
     */
    private function write($socket, string $data): void
    {
        $length = strlen($data);
        $written = 0;

        while ($written < $length) {
            $bytes = @fwrite($socket, substr($data, $written));

            if ($bytes === false || $bytes === 0) {
                throw new \RuntimeException('write to clamd failed');
            }

            $written += $bytes;
        }
    }

    /**
     * @param  resource  $socket
     */
    private function readResponse($socket): string
    {
        $response = '';

        while (! feof($socket)) {
            $buffer = fread($socket, 4096);

            if ($buffer === false || $buffer === '') {
                if (stream_get_meta_data($socket)['timed_out']) {
                    throw new \RuntimeException('clamd response timeout');
                }

                break;
            }

            $response .= $buffer;

            if (str_contains($response, "\0") || str_contains($response, "\n")) {
                break;
            }
        }

        return trim($response, "\0\n\r ");
    }

    private function parse(string $response): ScanResult
    {
        if (preg_match('/^stream:\s*OK$/i', $response)) {
            return new ScanResult(ScanResult::CLEAN);
        }

        if (preg_match('/^stream:\s*(.+)\s+FOUND$/i', $response, $matches)) {
            return new ScanResult(ScanResult::INFECTED, signature: trim($matches[1]));
        }

        return new ScanResult(ScanResult::UNAVAILABLE, reason: 'clamd: '.($response === '' ? 'empty response' : $response));
    }
}
