<?php

namespace Modules\HelpdeskTickets\Services;

use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Services\ErpContextService;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Reemplaza variables {{...}} en texto (respuestas predefinidas, macros,
 * plantillas) con los datos del ticket. Fuente ÚNICA de las variables
 * soportadas — la usan MacroExecutor y los canned replies del agente.
 */
class TicketVariableInterpolator
{
    /**
     * Variables disponibles, agrupadas para mostrar en la ayuda de los
     * formularios (macros, plantillas de creación de ticket).
     *
     * @return array<string, array<string, string>>
     */
    public static function availableVariables(): array
    {
        return [
            'Ticket y cliente' => [
                '{{ticket_number}}' => 'Número del ticket',
                '{{ticket_subject}}' => 'Asunto del ticket',
                '{{ticket_status}}' => 'Estado del ticket',
                '{{ticket_priority}}' => 'Prioridad del ticket',
                '{{ticket_category}}' => 'Categoría del ticket',
                '{{fecha}}' => 'Fecha actual (dd/mm/aaaa)',
                '{{customer_name}}' => 'Nombre del cliente',
                '{{customer_email}}' => 'Email del cliente',
                '{{customer_phone}}' => 'Teléfono del cliente',
                '{{agent_name}}' => 'Nombre del agente que crea/responde',
                '{{assignee_name}}' => 'Nombre del agente asignado',
            ],
            'Datos de gestión (ERP)' => [
                '{{erp_id_cliente}}' => 'ID del cliente en el ERP',
                '{{erp_nif}}' => 'NIF/CIF del cliente en el ERP',
                '{{erp_ciudad}}' => 'Ciudad del cliente en el ERP',
                '{{erp_saldo_pendiente}}' => 'Saldo pendiente de cobro',
                '{{erp_limite_credito}}' => 'Límite de crédito concedido',
                '{{erp_ultimo_pedido_numero}}' => 'Número del último pedido',
                '{{erp_ultimo_pedido_fecha}}' => 'Fecha del último pedido',
            ],
        ];
    }

    /**
     * @return array<string, string> mapa {{variable}} => valor
     */
    public function variables(Ticket $ticket): array
    {
        $ticket->loadMissing(['customer', 'assignee', 'status', 'category']);

        $subject = $ticket->subject ?? $ticket->title ?? '';
        $assignee = $ticket->assignee;
        $assigneeName = $assignee
            ? trim(($assignee->firstname ?? '').' '.($assignee->lastname ?? '')) ?: ($assignee->name ?? '')
            : 'Sin asignar';

        $agent = auth()->user();
        // User no tiene columna/accessor `name` — trim(firstname+lastname), igual
        // que $assigneeName arriba (bug encontrado 29-ago-2026: siempre caia al
        // fallback "Agente" porque ->name nunca existe en el modelo).
        $agentName = $agent
            ? (trim(($agent->firstname ?? '').' '.($agent->lastname ?? '')) ?: 'Agente')
            : 'Agente';

        return [
            '{{ticket_number}}' => (string) $ticket->ticket_number,
            '{{ticket_subject}}' => $subject,
            '{{ticket_title}}' => $subject, // alias retrocompatible con las macros
            '{{ticket_status}}' => $ticket->status?->name ?? '',
            '{{ticket_priority}}' => (string) ($ticket->priority ?? ''),
            '{{ticket_category}}' => $ticket->category?->name ?? '',
            '{{fecha}}' => now()->format('d/m/Y'),
            '{{customer_name}}' => $ticket->customer?->name ?? 'Cliente',
            '{{customer_email}}' => $ticket->customer?->email ?? '',
            '{{customer_phone}}' => $ticket->customer?->phone ?? '',
            '{{agent_name}}' => $agentName,
            '{{assignee_name}}' => $assigneeName,
        ];
    }

    /**
     * Interpola las variables en el texto. Si $ticket es null o el texto vacío,
     * devuelve el texto tal cual (sin romper).
     */
    public function interpolate(?string $text, ?Ticket $ticket): string
    {
        if ($text === null || $text === '' || $ticket === null) {
            return (string) $text;
        }

        $replacements = $this->variables($ticket);

        // Solo consulta el ERP si el texto realmente referencia una variable
        // {{erp_...}} — un canned reply/macro sin esas variables nunca paga
        // el coste de la llamada (que puede tardar hasta el timeout si el
        // manager esta lento). Best-effort: sin match/modulo apagado/manager
        // caido, las variables erp_* quedan vacias, nunca rompe la respuesta.
        if (str_contains($text, '{{erp_') && $ticket->customer) {
            $replacements = array_merge($replacements, $this->erpVariables($ticket->customer));
        }

        return strtr($text, $replacements);
    }

    /**
     * @return array<string, string>
     */
    private function erpVariables(Customer $customer): array
    {
        $empty = [
            '{{erp_id_cliente}}' => '',
            '{{erp_nif}}' => '',
            '{{erp_ciudad}}' => '',
            '{{erp_saldo_pendiente}}' => '',
            '{{erp_limite_credito}}' => '',
            '{{erp_ultimo_pedido_numero}}' => '',
            '{{erp_ultimo_pedido_fecha}}' => '',
        ];

        if (! helpdesk_erp_enabled() || ! class_exists(ErpContextService::class) || ! $customer->email) {
            return $empty;
        }

        try {
            $context = app(ErpContextService::class)->getCustomerContext($customer->email, $customer->phone);
        } catch (\Throwable) {
            return $empty;
        }

        if (! ($context['customer']['found'] ?? false)) {
            return $empty;
        }

        $data = $context['customer'];
        $lastOrder = $context['orders'][0] ?? null;

        return [
            '{{erp_id_cliente}}' => (string) ($data['id'] ?? ''),
            '{{erp_nif}}' => (string) ($data['nif'] ?? ''),
            '{{erp_ciudad}}' => (string) ($data['city'] ?? ''),
            '{{erp_saldo_pendiente}}' => $data['balance_pending'] !== null ? (string) $data['balance_pending'] : '',
            '{{erp_limite_credito}}' => $data['credit_limit'] !== null ? (string) $data['credit_limit'] : '',
            '{{erp_ultimo_pedido_numero}}' => (string) ($lastOrder['number'] ?? ''),
            '{{erp_ultimo_pedido_fecha}}' => (string) ($lastOrder['date'] ?? ''),
        ];
    }
}
