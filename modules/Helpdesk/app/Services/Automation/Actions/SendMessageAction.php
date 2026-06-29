<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\MessageReceived;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;
use Modules\Helpdesk\Services\Templates\LiquidRenderer;

class SendMessageAction implements AutomationAction
{
    public static function actionType(): string
    {
        return 'send_message';
    }

    public static function paramSchema(): array
    {
        return [
            'body' => ['type' => 'string', 'required' => true, 'description' => 'Cuerpo del mensaje (soporta Liquid templates)'],
            'is_internal' => ['type' => 'boolean', 'default' => false, 'description' => 'Si es una nota interna'],
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $context
     */
    public function execute(array $params, array $context): void
    {
        $conversation = $context['conversation'] ?? null;

        if (! $conversation instanceof Conversation) {
            return;
        }

        $body = $params['body'] ?? '';
        $isInternal = (bool) ($params['is_internal'] ?? false);

        if (blank($body)) {
            return;
        }

        $body = $this->renderTemplate($body, $conversation);

        $item = ConversationItem::create([
            'conversation_id' => $conversation->id,
            'type' => 'message',
            'body' => $body,
            'is_internal' => $isInternal,
            'metadata' => ['source' => 'automation'],
        ]);

        if (! $isInternal) {
            event(new MessageReceived($conversation, $item));
        }
    }

    private function renderTemplate(string $body, Conversation $conversation): string
    {
        // Use LiquidRenderer if available (created by another agent)
        $rendererClass = LiquidRenderer::class;

        if (class_exists($rendererClass)) {
            try {
                return app($rendererClass)->renderForConversation($body, $conversation);
            } catch (\Throwable $e) {
                Log::warning('SendMessageAction: LiquidRenderer failed, using raw body', ['error' => $e->getMessage()]);
            }
        }

        return $body;
    }
}
