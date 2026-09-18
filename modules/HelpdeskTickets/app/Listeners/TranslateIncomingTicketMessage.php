<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting as HelpdeskSetting;
use Modules\HelpdeskTickets\Events\MessageAdded;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Throwable;

/**
 * Detecta el idioma del cliente y traduce sus mensajes entrantes de ticket al
 * idioma del agente — mismo mecanismo que
 * Modules\HelpdeskTranslate\Listeners\TranslateIncomingMessage usa para
 * Conversaciones (mismo Customer::language, mismo CachedTranslator/DeepL, sin
 * duplicar proveedor/caché/cupo), solo que enganchado a MessageAdded en vez
 * de ConversationMessageCreated (Tickets no comparte esa tabla).
 *
 * No se registra directamente en el trait TranslatesMessage de
 * HelpdeskTranslate (composición de traits no es segura entre módulos sin
 * dependencia dura — un `use Trait` que no existe rompe la clase entera al
 * cargarla); la lógica se replica aquí, acotada a lo que hace falta.
 *
 * Se resuelve vía app() dentro de handle(), no por inyección en el
 * constructor — así, si HelpdeskTranslate no está instalado, la clase se
 * puede definir/cargar sin problema (el guard de registro en
 * HelpdeskTicketsEventServiceProvider ya evita que esto se encole, pero
 * evitar el type-hint en el constructor es una segunda capa de seguridad).
 */
class TranslateIncomingTicketMessage implements ShouldQueue
{
    use InteractsWithQueue;

    // 'helpdesk-events' NO la consume ningún worker activo (confirmado
    // 29-ago-2026: el comando real de webadmin-worker-helpdesk no la lista;
    // SendSlaBreachBroadcastNotification/SendSlaWarningBroadcastNotification
    // tienen el mismo problema preexistente, fuera de alcance aquí). Sin este
    // fix, la traducción de mensajes entrantes reales quedaría atascada en
    // Redis para siempre — solo "funcionaba" en las pruebas porque
    // handle() se llamó directo, sin pasar por la cola. 'notifications' sí
    // la consume ese mismo worker.
    public string $queue = 'notifications';

    public int $tries = 2;

    public int $backoff = 5;

    public function handle(MessageAdded $event): void
    {
        $item = $event->item;

        if (! $item || $item->user_id !== null || $item->is_internal) {
            return; // Solo mensajes del cliente (sin autor interno), no internos.
        }

        if (! helpdesk_translate_enabled() || ! class_exists(CachedTranslator::class)) {
            return;
        }

        $body = trim((string) ($item->body ?? ''));
        if ($body === '' || mb_strlen($body) < 3) {
            return;
        }

        $item->loadMissing('ticket.customer');
        $customer = $item->ticket?->customer;

        if (! $customer) {
            return;
        }

        $translator = app(CachedTranslator::class);
        $agentLocale = $this->agentLocale();
        $sourceLocale = $this->resolveCustomerLanguage($customer, $body, $translator);

        // "Un ticket tiene el idioma del cliente relacionado" — se deja en el
        // propio ticket (columna `detected_language`, ya existía en el schema
        // pero nada la usaba) independientemente de si hace falta traducir o
        // no, para poder filtrar/mostrarlo sin tener que resolver siempre la
        // relación customer. Antes de este fix quedaba NULL para siempre.
        if ($sourceLocale && $item->ticket && $item->ticket->detected_language !== $sourceLocale) {
            $item->ticket->forceFill(['detected_language' => $sourceLocale])->saveQuietly();
        }

        if (! $sourceLocale || $this->localesMatch($sourceLocale, $agentLocale)) {
            return;
        }

        $translated = $translator->translate($body, $agentLocale, $sourceLocale, 'auto_incoming');

        if ($translated && $translated !== $body) {
            $item->forceFill([
                'translated_body' => $translated,
                'source_locale' => $sourceLocale,
            ])->saveQuietly();
        }
    }

    public function failed(MessageAdded $event, Throwable $exception): void
    {
        Log::warning('TranslateIncomingTicketMessage failed', [
            'item_id' => $event->item?->id,
            'error' => $exception->getMessage(),
        ]);
    }

    private function resolveCustomerLanguage(Customer $customer, string $body, CachedTranslator $translator): ?string
    {
        $stored = $customer->language ? strtolower($customer->language) : null;

        // helpdesk_customers siembra 'es' por defecto — se trata como
        // "desconocido" hasta detectar de verdad un idioma del primer
        // mensaje (mismo criterio que TranslateIncomingMessage de Conversaciones).
        if ($stored && $stored !== 'es') {
            return $stored;
        }

        $detected = $translator->detectLanguage($body, 'auto_incoming');
        if (! $detected) {
            return $stored;
        }

        if ($detected !== $stored) {
            $customer->forceFill(['language' => $detected])->saveQuietly();
        }

        return $detected;
    }

    private function localesMatch(string $a, string $b): bool
    {
        return strtolower(substr($a, 0, 2)) === strtolower(substr($b, 0, 2));
    }

    private function agentLocale(): string
    {
        $value = HelpdeskSetting::get('helpdesktranslate.default_target');

        return (string) ($value !== null && $value !== '' ? $value : config('helpdesktranslate.default_target', 'es'));
    }
}
