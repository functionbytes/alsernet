<?php

namespace Modules\HelpdeskTickets\Services;

use Modules\Helpdesk\Models\Setting as HelpdeskSetting;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTranslate\Services\CachedTranslator;

/**
 * Traduce texto de agente (respuesta escrita en su idioma) al idioma del
 * cliente ANTES de enviarlo — mismo rol que
 * Modules\HelpdeskTranslate\Services\OutboundMessageTranslator::resolveOutboundText()
 * en Conversaciones, invocado de forma síncrona por el core Helpdesk justo
 * antes de despachar el envío real (SendOutboundMessageJob::resolveOutboundBody()):
 * ahí el cliente siempre veía el idioma del agente hasta que se agregó esa
 * llamada. Aquí se reutiliza el mismo Customer::language y el mismo
 * CachedTranslator/DeepL — sin proveedor, caché ni cupo propios.
 *
 * Guardado (helpdesk_translate_enabled() + class_exists()) para no requerir
 * HelpdeskTranslate como dependencia dura de HelpdeskTickets.
 */
class TicketOutboundTranslator
{
    /**
     * Devuelve $text traducido al idioma del cliente del ticket, o $text tal
     * cual si la traducción no aplica (módulo apagado, idioma desconocido,
     * mismo idioma que el agente, o cualquier fallo del traductor).
     */
    public function translateForCustomer(Ticket $ticket, string $text): string
    {
        $trimmed = trim($text);

        if ($trimmed === '' || mb_strlen($trimmed) < 3) {
            return $text;
        }

        if (! helpdesk_translate_enabled() || ! class_exists(CachedTranslator::class)) {
            return $text;
        }

        $customerLocale = strtolower((string) ($ticket->customer?->language ?? ''));
        if ($customerLocale === '') {
            return $text;
        }

        $agentLocale = strtolower($this->agentLocale());

        if (substr($customerLocale, 0, 2) === substr($agentLocale, 0, 2)) {
            return $text;
        }

        $translated = app(CachedTranslator::class)->translate($trimmed, $customerLocale, $agentLocale, 'auto_outgoing');

        return $translated ?: $text;
    }

    private function agentLocale(): string
    {
        $value = HelpdeskSetting::get('helpdesktranslate.default_target');

        return (string) ($value !== null && $value !== '' ? $value : config('helpdesktranslate.default_target', 'es'));
    }
}
