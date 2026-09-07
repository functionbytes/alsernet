<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Helpdesk\Events\ConversationCreated;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Support\EmailAuthenticationStatus;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\Supplier\Helpers\HtmlSanitizer;

class EmailInboundService
{
    /**
     * Process a parsed email payload and create or update a conversation.
     *
     * SEC-07 item 4 (fase 1, SOLO marcar): se verifica SPF/DKIM/DMARC del
     * From y se guarda el resultado en la metadata del mensaje + se logea
     * si falla — nunca se rechaza ni se pone en cuarentena el correo aquí
     * (eso queda para una fase 2 posterior, deliberadamente NO
     * implementada, ver EmailAuthenticationStatus). Lo único que SÍ se
     * aplica de forma activa es una decisión de autorización interna (no de
     * filtrado de correo): un From sin SPF/DKIM/DMARC alineado no tiene
     * autoridad para continuar el hilo de una conversación existente de
     * OTRO cliente por [CONV-{id}] — en ese caso se crea una conversación
     * nueva en vez de inyectar el mensaje en el hilo ajeno.
     *
     * @param  array{from: string, from_name: ?string, subject: string, body: string, message_id: ?string, auth_header?: ?string, auth_spf_hint?: ?string, auth_dkim_hint?: ?string}  $parsed
     */
    public function process(array $parsed): Conversation
    {
        $customer = Customer::firstOrCreate(
            ['email' => $parsed['from']],
            ['name' => $parsed['from_name'] ?? $parsed['from']],
        );

        $authStatus = EmailAuthenticationStatus::fromAuthenticationResultsHeader(
            $parsed['auth_header'] ?? null,
            $parsed['auth_spf_hint'] ?? null,
            $parsed['auth_dkim_hint'] ?? null,
        );

        $fromDomain = Str::after($parsed['from'], '@');
        $trustedForThreading = $fromDomain !== $parsed['from'] && $authStatus->isAlignedWith($fromDomain);

        if (! $trustedForThreading) {
            Log::warning('EmailInboundService: SPF/DKIM/DMARC no verificado o no alineado con el From — solo se registra (fase 1, no se rechaza el correo)', [
                'from' => $parsed['from'],
                'auth' => $authStatus->toArray(),
            ]);
        }

        $conversation = $this->resolveConversation($parsed['subject'], $customer->id, $trustedForThreading);

        $this->addMessage($conversation, $customer->id, $parsed['body'], $parsed['message_id'] ?? null, $authStatus);

        $conversation->update(['last_message_at' => now()]);

        $this->resolveErpCustomer($customer, $conversation);

        // wasRecentlyCreated distingue el hilo nuevo del que solo recibe otro
        // mensaje: resolveConversation() devuelve una u otra cosa según el
        // tag [CONV-{id}] del asunto.
        if ($conversation->wasRecentlyCreated && config('helpdesk.email_inbound.dispatch_conversation_created', false)) {
            ConversationCreated::dispatch($conversation);
        }

        return $conversation;
    }

    /**
     * Pide la búsqueda del cliente en el ERP, igual que hace HelpdeskTickets al
     * ingerir un correo.
     *
     * Se lanza también cuando el correo continúa un hilo ya abierto: si el
     * primer intento no encontró al cliente, o el ERP estaba caído, nada
     * volvía a intentarlo nunca. Lo que evita que esto machaque al ERP es el
     * enfriamiento de LinkCustomerToErpJob, no el hecho de no pedirlo.
     */
    private function resolveErpCustomer(Customer $customer, Conversation $conversation): void
    {
        if (! function_exists('helpdesk_erp_enabled') || ! helpdesk_erp_enabled()) {
            return;
        }

        if (! class_exists(LinkCustomerToErpJob::class)) {
            return;
        }

        LinkCustomerToErpJob::dispatch($customer->id, 'conversation', $conversation->id);
    }

    private function resolveConversation(string $subject, int $customerId, bool $trustedForThreading): Conversation
    {
        // Try to thread by [CONV-{id}] tag in subject — solo si el remitente
        // viene autenticado y alineado (ver process()): de lo contrario
        // cualquiera podría spoofear el From de un cliente real para
        // inyectar mensajes en su hilo existente.
        if ($trustedForThreading && preg_match('/\[CONV-(\d+)\]/', $subject, $m)) {
            $conversation = Conversation::query()
                ->where('id', (int) $m[1])
                ->where('customer_id', $customerId)
                ->whereHas('status', fn ($q) => $q->where('is_open', true))
                ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        return $this->createConversation($subject, $customerId);
    }

    private function createConversation(string $subject, int $customerId): Conversation
    {
        $statusId = ConversationStatus::query()
            ->where('is_default', true)
            ->orWhere('is_open', true)
            ->orderByDesc('is_default')
            ->value('id') ?? 1;

        $conversation = Conversation::create([
            'customer_id' => $customerId,
            'channel' => 'email',
            'subject' => $subject ?: 'Email sin asunto',
            'last_message_at' => now(),
        ]);
        $conversation->status_id = $statusId;
        $conversation->save();

        return $conversation;
    }

    private function addMessage(Conversation $conversation, int $customerId, string $body, ?string $externalId, EmailAuthenticationStatus $authStatus): void
    {
        if ($externalId && $this->isDuplicate($conversation->id, $externalId)) {
            Log::info('EmailInboundService: duplicate message skipped', ['external_id' => $externalId]);

            return;
        }

        // El proveedor del webhook (mailgun/sendgrid/postmark/generic) puede
        // enviar el cuerpo en texto plano o, si no hay parte de texto, en
        // HTML crudo del cliente: se sanea siempre porque aquí no se sabe cuál
        // de los dos llegó.
        $body = HtmlSanitizer::clean($body);

        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'author_id' => $customerId,
            'type' => 'message',
            'body' => $body ?: '[sin contenido]',
            'external_id' => $externalId,
            'is_internal' => false,
            // 'email_auth_status' es solo informativo (fase 1, ver
            // process()) — nunca se usa para descartar el mensaje.
            'metadata' => ['platform' => 'email', 'email_auth_status' => $authStatus->toArray()],
        ]);
    }

    private function isDuplicate(int $conversationId, string $externalId): bool
    {
        return ConversationItem::query()
            ->where('conversation_id', $conversationId)
            ->where('external_id', $externalId)
            ->exists();
    }
}
