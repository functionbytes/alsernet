<?php

namespace Modules\HelpdeskChatFlow\Services;

use Closure;
use Illuminate\Support\Facades\Schema;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskAiPrompts\Models\AiActionRun;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\Simulation\CapturesBotMessages;

/**
 * Nota interna estructurada que se publica cuando el bot cede la conversación a
 * una persona: motivo, caso de prompt, procedimiento, datos recogidos (con
 * email/teléfono enmascarados), acciones ejecutadas y, si está activo, el
 * resumen IA. Así el agente no tiene que volver a pedir nada al cliente.
 */
class HandoffContextNote
{
    public const REASON_NODE = 'Traspaso definido en el flujo (nodo de transferencia)';

    public const REASON_END = 'Fin de flujo con traspaso a un agente';

    public const REASON_AI_ESCALATION = 'Escalado por el asistente IA (no pudo resolverlo)';

    public const REASON_ACTION_ERROR = 'Error al ejecutar una acción';

    public const REASON_CUSTOMER_REQUEST = 'El cliente pidió hablar con una persona';

    public const REASON_TIMEOUT = 'Tiempo de espera del nodo agotado';

    /** Ventana (minutos) en la que no se repite la nota para la misma sesión. */
    private const DEDUPE_MINUTES = 2;

    private const MAX_EXTRA_FIELDS = 12;

    private const MAX_VALUE_LENGTH = 120;

    private const MAX_ACTIONS = 10;

    /** Claves internas o ruidosas que nunca se muestran al agente. */
    private const HIDDEN_KEYS = [
        'conversation_id', 'customer_lang', 'last_input', 'added_tags', 'created_ticket_id',
        'customer_ps_id', 'customer_erp_id', 'current_product_id', 'trace_id',
    ];

    /** Claves con etiqueta propia; el resto de datos va debajo, genérico. */
    private const LABELED_KEYS = [
        'order_ref', 'order_reference', 'order_number', 'order_id', 'pedido',
        'customer_email', 'email', 'talla', 'size', 'current_product_title',
        'cart_total', 'cart_count', 'customer_name', 'name',
        'identity_verified', 'customer_identified_via_otp',
    ];

    /**
     * Publica la nota. No hace nada si ya hay una de la misma sesión en los
     * últimos minutos, ni en el simulador. Devuelve si se creó.
     *
     * @param  Closure(): ?string|null  $summary  Resumen IA perezoso (solo se evalúa si se publica la nota)
     */
    public function post(Conversation $conversation, ChatFlowSession $session, string $reason, ?Closure $summary = null): bool
    {
        if ($conversation instanceof CapturesBotMessages) {
            return false;
        }

        if ($this->alreadyPosted($conversation, $session)) {
            return false;
        }

        $body = $this->build($conversation, $session, $reason, $summary ? $summary() : null);

        $conversation->items()->create([
            'type' => 'message',
            'body' => $body,
            'is_internal' => true,
            'metadata' => array_filter([
                'sent_by_chatflow' => true,
                'handoff_context' => true,
                'session_id' => $session->id,
            ], fn ($value) => $value !== null),
        ]);

        return true;
    }

    public function build(Conversation $conversation, ChatFlowSession $session, string $reason, ?string $summary = null): string
    {
        $context = is_array($session->context) ? $session->context : [];

        $sections = ["📋 Contexto del traspaso\n\nMotivo: ".$reason];

        $procedure = $this->procedureLines($session, $context);
        if ($procedure !== []) {
            $sections[] = implode("\n", $procedure);
        }

        $collected = $this->collectedData($context);
        $sections[] = "Datos recogidos:\n".($collected === [] ? '- (ninguno)' : implode("\n", $collected));

        $actions = $this->actionLines($conversation, $context);
        if ($actions !== []) {
            $sections[] = "Acciones ejecutadas:\n".implode("\n", $actions);
        }

        if ($summary !== null && trim($summary) !== '') {
            $sections[] = "Resumen del bot:\n".trim($summary);
        }

        return implode("\n\n", $sections);
    }

    private function alreadyPosted(Conversation $conversation, ChatFlowSession $session): bool
    {
        return $conversation->items()
            ->where('is_internal', true)
            ->where('created_at', '>=', now()->subMinutes(self::DEDUPE_MINUTES))
            ->get()
            ->contains(function ($item) use ($session): bool {
                $metadata = is_array($item->metadata) ? $item->metadata : [];

                if (empty($metadata['handoff_context'])) {
                    return false;
                }

                return $session->id === null || (int) ($metadata['session_id'] ?? 0) === (int) $session->id;
            });
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    private function procedureLines(ChatFlowSession $session, array $context): array
    {
        $lines = [];

        $case = $context['ai_case'] ?? $context['ai_pending_case'] ?? null;
        if (is_string($case) && $case !== '') {
            $lines[] = 'Caso de prompt: '.$case;
        }

        $flowName = $session->chatFlow?->name;
        if (is_string($flowName) && $flowName !== '') {
            $lines[] = 'Flujo: '.$flowName;
        }

        $stack = $this->callStackNames($context['_call_stack'] ?? null);
        if ($stack !== []) {
            $lines[] = 'Procedimiento: '.implode(' > ', $stack);
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function callStackNames(mixed $stack): array
    {
        if (! is_array($stack)) {
            return [];
        }

        $names = [];
        foreach ($stack as $frame) {
            $name = is_array($frame)
                ? ($frame['flow_name'] ?? $frame['name'] ?? $frame['flow_id'] ?? null)
                : $frame;

            if (is_scalar($name) && (string) $name !== '') {
                $names[] = (string) $name;
            }
        }

        return $names;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    private function collectedData(array $context): array
    {
        $lines = [];

        $order = $this->firstScalar($context, ['order_ref', 'order_reference', 'order_number', 'order_id', 'pedido']);
        if ($order !== null) {
            $lines[] = '- Pedido: '.$order;
        }

        $email = $this->firstScalar($context, ['customer_email', 'email']);
        if ($email !== null) {
            $lines[] = '- Email: '.$this->maskEmail($email);
        }

        $name = $this->firstScalar($context, ['customer_name', 'name']);
        if ($name !== null) {
            $lines[] = '- Nombre: '.$name;
        }

        $size = $this->firstScalar($context, ['talla', 'size']);
        if ($size !== null) {
            $lines[] = '- Talla: '.$size;
        }

        $product = $this->firstScalar($context, ['current_product_title']);
        if ($product !== null) {
            $lines[] = '- Producto actual: '.$product;
        }

        $cart = $this->cartSummary($context);
        if ($cart !== null) {
            $lines[] = '- Carrito: '.$cart;
        }

        $verified = ! empty($context['identity_verified']) || ! empty($context['customer_identified_via_otp']);
        $lines[] = '- Verificado: '.($verified ? 'sí' : 'no');

        foreach ($this->extraFields($context) as $key => $value) {
            $lines[] = '- '.$key.': '.$value;
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    private function extraFields(array $context): array
    {
        $extra = [];

        foreach ($context as $key => $value) {
            if (! is_string($key) || ! $this->isDisplayable($key, $value)) {
                continue;
            }

            $extra[$key] = $this->limit($this->maskValue($key, (string) $value));

            if (count($extra) >= self::MAX_EXTRA_FIELDS) {
                break;
            }
        }

        return $extra;
    }

    private function isDisplayable(string $key, mixed $value): bool
    {
        if (str_starts_with($key, '_') || str_starts_with($key, 'ai_')) {
            return false;
        }

        if (in_array($key, self::HIDDEN_KEYS, true) || in_array($key, self::LABELED_KEYS, true)) {
            return false;
        }

        if (str_contains($key, 'buffer') || str_contains($key, 'trace')) {
            return false;
        }

        return is_scalar($value) && ! is_bool($value) && trim((string) $value) !== '';
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, string>  $keys
     */
    private function firstScalar(array $context, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $context[$key] ?? null;

            if (is_scalar($value) && ! is_bool($value) && trim((string) $value) !== '') {
                return $this->limit($this->maskValue($key, (string) $value));
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function cartSummary(array $context): ?string
    {
        $total = $context['cart_total'] ?? null;
        $count = $context['cart_count'] ?? null;
        $parts = [];

        if (is_scalar($total) && (string) $total !== '') {
            $parts[] = 'total '.$total;
        }

        if (is_scalar($count) && (string) $count !== '') {
            $parts[] = $count.' uds.';
        }

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    private function actionLines(Conversation $conversation, array $context): array
    {
        if (! class_exists(AiActionRun::class)) {
            return [];
        }

        $connection = (new AiActionRun)->getConnectionName();
        if (! Schema::connection($connection)->hasTable('helpdesk_ai_action_runs')) {
            return [];
        }

        $traceId = (string) ($context['_trace_id'] ?? '');

        $runs = AiActionRun::query()
            ->where(function ($query) use ($conversation, $traceId): void {
                $query->where('conversation_id', $conversation->id);

                if ($traceId !== '') {
                    $query->orWhere('trace_id', $traceId);
                }
            })
            ->oldest('id')
            ->limit(self::MAX_ACTIONS)
            ->get();

        return $runs->map(function (AiActionRun $run): string {
            $line = '- '.$run->action_key.' ('.$run->status.')';

            return $run->error ? $line.': '.$this->limit((string) $run->error) : $line;
        })->all();
    }

    private function maskValue(string $key, string $value): string
    {
        if (preg_match('/phone|telefono|teléfono|movil|móvil|mobile|whatsapp|(^|_)tel($|_)/i', $key) === 1) {
            return $this->maskPhone($value);
        }

        return preg_replace_callback(
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
            fn (array $match): string => $this->maskEmail($match[0]),
            $value,
        ) ?? $value;
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);

        if (count($parts) !== 2 || $parts[0] === '') {
            return '***';
        }

        return mb_substr($parts[0], 0, 1).'***@'.$parts[1];
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (strlen($digits) <= 3) {
            return '***';
        }

        return str_repeat('*', strlen($digits) - 3).substr($digits, -3);
    }

    private function limit(string $value): string
    {
        return mb_strlen($value) > self::MAX_VALUE_LENGTH
            ? mb_substr($value, 0, self::MAX_VALUE_LENGTH).'…'
            : $value;
    }
}
