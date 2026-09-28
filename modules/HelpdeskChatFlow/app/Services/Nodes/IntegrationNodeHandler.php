<?php

namespace Modules\HelpdeskChatFlow\Services\Nodes;

use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowHttpRequester;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\ChatFlowOrderLookup;
use Modules\HelpdeskChatFlow\Services\Concerns\EvaluatesBusinessHours;
use Modules\HelpdeskChatFlow\Services\Concerns\PostsBotMessages;
use Modules\HelpdeskChatFlow\Services\Concerns\RendersNodeMessages;

/**
 * Nodes that consult external systems: order lookup (ERP/PrestaShop), generic
 * HTTP requests and business hours.
 */
class IntegrationNodeHandler implements NodeHandler
{
    use EvaluatesBusinessHours, PostsBotMessages, RendersNodeMessages;

    public const TYPES = ['order_lookup', 'http_request', 'business_hours'];

    public function __construct(
        private readonly ChatFlowOrderLookup $orderLookup,
        private readonly ChatFlowHttpRequester $httpRequester,
        private readonly ChatFlowLocalizer $localizer,
    ) {}

    public function types(): array
    {
        return self::TYPES;
    }

    public function handle(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        return match ($node['type']) {
            'order_lookup' => $this->executeOrderLookup($node, $session, $conversation),
            'http_request' => $this->executeHttpRequest($node, $session, $conversation),
            'business_hours' => $this->executeBusinessHours($node, $session),
        };
    }

    private function executeOrderLookup(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $orderVar = $data['order_variable'] ?? 'numero_pedido';
        $orderId = $session->getContextValue($orderVar);

        // Exige identidad verificada por OTP, no solo customer_identified — un
        // nodo identify_customer con require_otp=false deja ese contexto con
        // el email que haya escrito el usuario en texto libre, sin verificar
        // que sea suyo de verdad. Mismo guard que
        // ChatFlowAgentService::executeTool('lookup_order', ...); antes este
        // nodo (a diferencia del tool de IA) no comprobaba nada — un flow que
        // encadenara identify_customer(require_otp=false) → order_lookup
        // exponía el pedido de cualquiera con solo teclear su email.
        if (! $session->getContextValue('customer_identified_via_otp')) {
            $order = ['found' => false];
        } else {
            $customer = [
                'erp_id' => $session->getContextValue('customer_erp_id'),
                'ps_id' => $session->getContextValue('customer_ps_id'),
                'email' => $session->getContextValue('customer_email'),
            ];

            $order = $this->orderLookup->lookup($orderId, $customer, $data['source'] ?? 'auto');
        }

        if ($order['found']) {
            // Seed every field a found_message template might interpolate, including
            // order_id/order_date which custom templates and the default message use.
            $session->setContextValues([
                'order_found' => true,
                'order_id' => $order['order_id'] ?? '',
                'order_date' => $order['date'] ?? '',
                'order_status' => $order['status'] ?? '',
                'order_total' => $order['total'] ?? '',
                'order_tracking' => $order['tracking'] ?? '',
            ]);

            $body = ! empty($data['found_message'])
                ? $this->interpolateContext($data['found_message'], $session->context ?? [])
                : $this->defaultOrderMessage($order);
        } else {
            $session->setContextValue('order_found', false);
            $body = $data['not_found_message']
                ?? 'No he encontrado ese pedido asociado a tu cuenta. Verifica el número e inténtalo de nuevo.';
        }

        $this->postBotMessage($conversation, $node['id'], $this->localizeForCustomer($body, $session));

        return $this->getFirstChildId($node, $session);
    }

    /**
     * @param  array<string,mixed>  $order
     */
    private function defaultOrderMessage(array $order): string
    {
        $lines = ["📦 Pedido #{$order['order_id']}"];

        if ($order['status']) {
            $lines[] = "Estado: {$order['status']}";
        }
        if ($order['date']) {
            $lines[] = "Fecha: {$order['date']}";
        }
        if ($order['total']) {
            $lines[] = "Total: {$order['total']}";
        }
        if ($order['tracking']) {
            $lines[] = "Seguimiento: {$order['tracking']}";
        }

        return implode("\n", $lines);
    }

    private function executeHttpRequest(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $result = $this->httpRequester->send($data, $session->context ?? []);

        $saveTo = $data['save_to'] ?? 'http_response';
        $session->setContextValues([
            $saveTo => $result['value'],
            $saveTo.'_ok' => $result['ok'],
            $saveTo.'_status' => $result['status'],
        ]);

        if (! empty($data['show_message']) && ! empty($data['message_template'])) {
            $this->postBotMessage($conversation, $node['id'], $this->interpolateContext($data['message_template'], $session->context ?? []));
        }

        return $this->getFirstChildId($node, $session);
    }

    private function executeBusinessHours(array $node, ChatFlowSession $session): ?string
    {
        $data = $node['data'] ?? [];
        $within = $this->isWithinBusinessHours($data);

        $session->setContextValue('within_business_hours', $within);

        return $this->getFirstChildId($node, $session);
    }
}
