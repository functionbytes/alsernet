<?php

namespace Modules\HelpdeskAiPrompts\Services\Flow;

use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionExecutor;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionParameters;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;
use Modules\HelpdeskChatFlow\Services\Concerns\PostsBotMessages;
use Modules\HelpdeskChatFlow\Services\Concerns\RendersNodeMessages;
use Modules\HelpdeskChatFlow\Services\Nodes\NodeHandler;

/**
 * Nodo `ai_action` de ChatFlow: ejecuta una acción del catálogo del asistente
 * IA (mismo ActionExecutor que usa la IA, con origen 'flow') y deja el
 * resultado en el contexto de la sesión.
 *
 * Solo se registra si HelpdeskChatFlow existe (ver ServiceProvider).
 */
class AiActionNodeHandler implements NodeHandler
{
    use PostsBotMessages, RendersNodeMessages;

    public const TYPE = 'ai_action';

    private const MAX_FLAT_FIELDS = 30;

    private const AFFIRMATIVE = ['si', 'sí', 'yes', 'true', '1', 'ok', 'vale', 'confirmo'];

    public function __construct(
        private readonly ActionExecutor $executor,
        private readonly ChatFlowLocalizer $localizer,
    ) {}

    public function types(): array
    {
        return [self::TYPE];
    }

    public function handle(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $data = $node['data'] ?? [];
        $context = $session->context ?? [];
        $saveTo = trim((string) ($data['save_to'] ?? '')) ?: 'accion';

        $args = $this->buildArgs((array) ($data['args'] ?? []), $context);
        unset($args[ActionParameters::CONFIRM]);

        if ($this->isConfirmed($data['confirmed_variable'] ?? null, $context)) {
            $args[ActionParameters::CONFIRM] = true;
        }

        $result = $this->executor->run(
            (string) ($data['action_key'] ?? ''),
            $args,
            $this->actionContext($context, $conversation),
            'flow',
        );

        $session->setContextValues($this->outputs($saveTo, $result));

        if (! empty($data['show_message']) && ! empty($data['message_template'])) {
            $body = $this->interpolateContext($data['message_template'], $session->context ?? []);
            $this->postBotMessage($conversation, $node['id'], $body);
        }

        if (! $result['ok'] && ($data['on_error'] ?? 'continue') === 'handoff') {
            return $this->handoff($node, $session, $conversation);
        }

        return $this->getFirstChildId($node, $session);
    }

    /**
     * @param  array<string, mixed>  $templates
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function buildArgs(array $templates, array $context): array
    {
        $args = [];

        foreach ($templates as $param => $template) {
            $args[(string) $param] = is_string($template) ? $this->resolveTemplate($template, $context) : $template;
        }

        return $args;
    }

    /**
     * Una plantilla que es solo `{{ruta}}` conserva el tipo del valor; si
     * mezcla texto, se interpola como cadena. Una variable ausente queda vacía.
     *
     * @param  array<string, mixed>  $context
     */
    private function resolveTemplate(string $template, array $context): mixed
    {
        if (preg_match('/^\s*\{\{\s*([\w.]+)\s*\}\}\s*$/', $template, $m)) {
            return $this->lookup($context, $m[1]);
        }

        return preg_replace_callback('/\{\{\s*([\w.]+)\s*\}\}/', function (array $m) use ($context): string {
            $value = $this->lookup($context, $m[1]);

            return is_scalar($value) ? (string) $value : '';
        }, $template);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function lookup(array $context, string $path): mixed
    {
        if (array_key_exists($path, $context)) {
            return $context[$path];
        }

        $value = $context;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function isConfirmed(mixed $variable, array $context): bool
    {
        if (! is_string($variable) || trim($variable) === '') {
            return false;
        }

        $value = $this->lookup($context, trim($variable));

        if (is_bool($value)) {
            return $value;
        }

        return is_scalar($value) && in_array(mb_strtolower(trim((string) $value)), self::AFFIRMATIVE, true);
    }

    /**
     * Los datos del cliente solo salen del contexto si su identidad está
     * verificada (OTP o sesión firmada por la tienda), nunca de lo que teclea.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function actionContext(array $context, Conversation $conversation): array
    {
        $verified = ! empty($context['customer_identified_via_otp']) || ! empty($context['identity_verified']);

        return [
            'verified' => $verified,
            'customer_email' => $verified ? ($context['customer_email'] ?? null) : null,
            'customer_ps_id' => $verified ? ($context['customer_ps_id'] ?? null) : null,
            'customer_erp_id' => $verified ? ($context['customer_erp_id'] ?? null) : null,
            'conversation_id' => $context['conversation_id'] ?? $conversation->id,
            'trace_id' => $context['_trace_id'] ?? null,
            'channel' => $context['_channel'] ?? null,
            'locale' => $context['customer_lang'] ?? null,
        ];
    }

    /**
     * @param  array{ok: bool, content: string, status: string}  $result
     * @return array<string, mixed>
     */
    private function outputs(string $saveTo, array $result): array
    {
        $outputs = [
            $saveTo.'_ok' => $result['ok'],
            $saveTo.'_status' => $result['status'],
        ];

        $decoded = json_decode($result['content'], true);

        if (! is_array($decoded)) {
            return [$saveTo => $result['content']] + $outputs;
        }

        $outputs[$saveTo] = $decoded;
        $flat = 0;

        foreach ($decoded as $field => $value) {
            if ($flat >= self::MAX_FLAT_FIELDS) {
                break;
            }

            $name = preg_replace('/\W+/', '_', (string) $field);
            if (! is_scalar($value) || $name === '') {
                continue;
            }

            $outputs[$saveTo.'_'.$name] ??= $value;
            $flat++;
        }

        return $outputs;
    }

    private function handoff(array $node, ChatFlowSession $session, Conversation $conversation): ?string
    {
        $message = $this->localizeForCustomer('Un momento, te transfiero con un agente.', $session);
        $this->postBotMessage($conversation, $node['id'], $message);

        $conversation->releaseFromBot();
        $session->update(['status' => 'transferred', 'ended_at' => now()]);

        return null;
    }
}
