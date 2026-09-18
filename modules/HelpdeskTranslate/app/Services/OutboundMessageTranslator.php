<?php

namespace Modules\HelpdeskTranslate\Services;

use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Setting;

/**
 * Resuelve el texto que realmente debe salir hacia un canal externo
 * (WhatsApp/Facebook/Instagram) cuando la auto-traducción saliente está
 * activa — a diferencia del listener TranslateOutgoingMessage, que solo
 * calcula `outgoing_translated_body` para mostrárselo al agente en su propio
 * panel, DESPUÉS de que SendOutboundMessageJob ya despachó el mensaje
 * original. Ese cálculo asíncrono nunca llegaba a tiempo para influir en lo
 * que el cliente de WhatsApp recibía: el cliente siempre veía el idioma del
 * agente, nunca el suyo (el widget de chat en vivo sí mostraba la traducción,
 * porque no pasa por una API externa inmutable).
 *
 * Este servicio se invoca de forma SÍNCRONA justo antes de despachar el job
 * de envío, con la misma lógica de elegibilidad que el listener (mismo
 * toggle, mismo idioma detectado del cliente, mismo locale del agente), y
 * persiste el resultado en el item de inmediato para que el listener
 * asíncrono, cuando corra después, encuentre un cache-hit y no repita coste.
 */
class OutboundMessageTranslator
{
    public function __construct(
        private readonly CachedTranslator $translator,
    ) {}

    /**
     * @param  ConversationItem  $item  el item ya creado (con su conversation cargada o cargable)
     * @param  string  $text  el texto a enviar tal como el llamador lo tenía resuelto (ya limpio de @menciones, etc.)
     * @return string el texto traducido a enviar, o $text sin tocar si no aplica ninguna condición
     */
    public function resolveOutboundText(ConversationItem $item, string $text): string
    {
        if (trim($text) === '' || $item->is_internal) {
            return $text;
        }

        if (! helpdesk_translate_enabled() || ! $this->autoOutgoingEnabled()) {
            return $text;
        }

        $conversation = $item->conversation ?? $item->conversation()->first();
        $customer = $conversation?->customer;
        $customerLang = $customer?->language ? strtolower($customer->language) : null;

        if (! $customerLang) {
            return $text;
        }

        $agentLocale = $this->agentLocale();

        if (substr($customerLang, 0, 2) === substr($agentLocale, 0, 2)) {
            return $text;
        }

        // El cupo diario se comprueba y descuenta dentro de translate(), solo
        // en cache-miss real — ver CachedTranslator::quotaExceededForCall().
        $translated = $this->translator->translate($text, $customerLang, $agentLocale, feature: 'auto_outgoing');

        if (! $translated || $translated === $text) {
            return $text;
        }

        // Deja constancia inmediata en el item (mismas columnas que el
        // listener asíncrono) para que el panel del agente muestre exactamente
        // lo que se envió, sin esperar a que el listener recalcule lo mismo.
        $item->forceFill([
            'outgoing_translated_body' => $translated,
            'outgoing_target_locale' => $customerLang,
        ])->saveQuietly();

        return $translated;
    }

    private function autoOutgoingEnabled(): bool
    {
        $stored = Setting::get('helpdesktranslate.auto_translate_outgoing');

        if ($stored === null || $stored === '') {
            return (bool) config('helpdesktranslate.auto_translate_outgoing', false);
        }

        return (bool) $stored;
    }

    private function agentLocale(): string
    {
        $stored = Setting::get('helpdesktranslate.default_target');

        return strtolower((string) (($stored === null || $stored === '')
            ? config('helpdesktranslate.default_target', 'es')
            : $stored));
    }
}
