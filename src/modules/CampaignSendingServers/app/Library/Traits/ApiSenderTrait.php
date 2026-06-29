<?php

namespace Modules\CampaignSendingServers\Library\Traits;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mime\Email;

/**
 * Helpers compartidos por los SendingServer*Api: extraer cuerpo del Email y
 * enviar requests HTTP con Guzzle (incluido en Laravel via HTTP facade).
 */
trait ApiSenderTrait
{
    /**
     * Convierte un Symfony\Component\Mime\Email a un payload sencillo
     * (from, to, subject, html, text, headers, attachments) que cada
     * proveedor mapea a su formato JSON.
     */
    protected function emailToPayload(Email $email): array
    {
        $from = $email->getFrom()[0] ?? null;
        $to = $email->getTo()[0] ?? null;
        $replyTo = $email->getReplyTo()[0] ?? null;

        $headers = [];
        foreach ($email->getHeaders()->all() as $header) {
            $name = $header->getName();
            // Skip headers que el proveedor genera por sí mismo
            if (in_array(strtolower($name), ['from', 'to', 'subject', 'date', 'mime-version', 'content-type', 'content-transfer-encoding'], true)) {
                continue;
            }
            $headers[$name] = $header->getBodyAsString();
        }

        $attachments = [];
        foreach ($email->getAttachments() as $att) {
            $attachments[] = [
                'name' => $att->getFilename() ?? 'attachment',
                'type' => $att->getContentType(),
                'content' => base64_encode($att->getBody()),
            ];
        }

        return [
            'from_email' => $from?->getAddress(),
            'from_name' => $from?->getName(),
            'to_email' => $to?->getAddress(),
            'to_name' => $to?->getName(),
            'reply_to' => $replyTo?->getAddress(),
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody(),
            'text' => $email->getTextBody(),
            'headers' => $headers,
            'attachments' => $attachments,
        ];
    }

    /**
     * Cliente HTTP del módulo. Usa `Illuminate\Support\Facades\Http` que
     * envuelve Guzzle. Timeout corto: el envío no debe bloquear más de 30s.
     */
    protected function http()
    {
        return Http::timeout(30)->retry(2, 500);
    }
}
