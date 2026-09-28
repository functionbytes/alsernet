<?php

namespace Modules\HelpdeskTickets\Services\TicketDetail;

use Illuminate\Support\Str;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAttachment;
use Modules\HelpdeskTickets\Services\TicketDetail\Support\FormatsFileSize;

/**
 * Pestaña Formulario del panel de detalle: datos de origen cuando el ticket
 * viene de un formulario. Extraído de TicketDetailDataService (30-sep-2026)
 * al trocear ese servicio por sección de panel.
 */
class FormBuilder
{
    use FormatsFileSize;

    /**
     * Datos del formulario de origen.
     *
     * Contrato con dos estados distintos, a propósito (bug real de UI
     * encontrado en QA, TCK-2026-00009: el chip de origen bajo el título
     * decía "Formulario" mientras la pestaña Formulario decía "Este ticket
     * no proviene de un formulario", una contradicción directa porque antes
     * este método devolvía null tanto si el origen NO era un formulario
     * COMO si SÍ lo era pero custom_fields venía vacío — el JS no podía
     * distinguir ambos casos):
     *  - null puro: el ORIGEN del ticket no es un canal de formulario. El JS
     *    mantiene el mensaje actual ("Este ticket no proviene de un
     *    formulario. Origen: {label}.").
     *  - array con is_form_origin=true: el origen SÍ es un formulario.
     *    'fields' es el array de custom_fields capturado, o [] (nunca null)
     *    cuando el formulario no capturó ningún campo extra — el JS debe
     *    distinguir este caso con un mensaje honesto distinto ("Este ticket
     *    es de un formulario, pero no se capturaron campos adicionales."),
     *    no el mensaje de "no proviene de un formulario".
     */
    public function build(Ticket $ticket): ?array
    {
        $fields = $ticket->custom_fields ?: [];

        // Antes esto exigía que el origen fuese exactamente 'formulario' o
        // 'web_form'. En la base hay tickets con origen 'form' (que el resto
        // de la pantalla ya rotula como Formulario) y 16 con origen 'email'
        // que traen campos porque el formulario llega por correo: a todos
        // ellos el panel les respondía "este ticket no proviene de un
        // formulario" teniendo los campos guardados. El criterio pasa a ser
        // el dato: si hay campos capturados, se enseñan.
        if ($fields === []) {
            return null;
        }

        // Claves técnicas del envío frente a campos que rellenó el cliente.
        // El mockup separa "Campos enviados" de "Trazabilidad del envío";
        // aquí la separación es por nombre de clave, y la sección técnica
        // solo se emite si el formulario capturó alguno de esos datos —
        // ninguno de los formularios de esta instalación los guarda hoy, así
        // que lo normal es que salga vacía en vez de inventada.
        $traceKeys = [
            'form_key' => 'formulario',
            'page' => 'página',
            'page_url' => 'página',
            'url' => 'página',
            'referrer' => 'procedencia',
            'ip' => 'IP',
            'client_ip' => 'IP',
            'user_agent' => 'navegador',
            'utm_source' => 'campaña',
            'utm_medium' => 'medio',
            'utm_campaign' => 'campaña',
            'rgpd' => 'RGPD',
            'gdpr' => 'RGPD',
            'privacy_accepted' => 'RGPD',
            'consent' => 'RGPD',
            'submitted_at' => 'enviado',
            'store_url' => 'tienda',
        ];

        $submitted = [];
        $trace = [];
        // El texto libre se enseña como cita destacada arriba; repetirlo
        // otra vez dentro de la lista de campos era ruido.
        $freeText = $this->formFreeTextFor($fields);

        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_filter($value, 'is_scalar'));
            }

            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            $row = ['key' => $key, 'label' => $traceKeys[$key] ?? Str::headline((string) $key), 'value' => (string) $value];

            if (isset($traceKeys[$key])) {
                $trace[] = $row;
            } elseif ($freeText === null || trim((string) $value) !== $freeText) {
                $submitted[] = $row;
            }
        }

        // Adjuntos que llegaron con el formulario: son los del cliente, ya
        // cargados como TicketAttachment (mismo origen que $customerFiles en
        // FilesBuilder).
        $attachments = TicketAttachment::query()
            ->whereHas('message', fn ($q) => $q->where('ticket_id', $ticket->id))
            ->latest()
            ->get()
            ->map(fn (TicketAttachment $a) => [
                'name' => $a->original_filename ?: $a->filename,
                'size_human' => $this->humanSize((int) $a->size),
                'url_download' => route('manager.helpdesk.tickets.message-attachments.download', [$ticket, $a->id]),
            ])->values()->all();

        return [
            'is_form_origin' => in_array($ticket->sourceSlug(), ['formulario', 'form', 'web_form'], true),
            'source_label' => $ticket->sourceSlug(),
            // Cita destacada del mockup: lo que el cliente escribió de su
            // puño, separado de los campos con valor de lista.
            'message' => $freeText,
            'fields' => $fields,
            'submitted' => $submitted,
            'trace' => $trace,
            'attachments' => $attachments,
        ];
    }

    /**
     * Campo de texto libre de un formulario (el "mensaje"), si lo hay.
     *
     * @param  array<string, mixed>  $fields
     */
    private function formFreeTextFor(array $fields): ?string
    {
        foreach (['message', 'mensaje', 'comment', 'comments', 'comentario', 'comentarios',
            'observations', 'observaciones', 'consulta', 'descripcion', 'description',
            'body', 'texto', 'pregunta'] as $key) {
            $value = $fields[$key] ?? null;

            if (is_string($value) && mb_strlen(trim($value)) >= 20) {
                return trim($value);
            }
        }

        return null;
    }
}
