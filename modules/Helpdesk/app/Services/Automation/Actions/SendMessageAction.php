<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;
use Modules\Helpdesk\Services\ConversationMessageService;
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

        // Route through the same service a manual agent reply uses so this
        // behaves identically: attributed to a real user (not left with
        // user_id=null, which the UI renders as if the CUSTOMER had sent it —
        // see isFromCustomer()/thread.blade.php), and actually dispatched to
        // the customer's external channel (WhatsApp/Facebook/Instagram) via
        // SendOutboundMessageJob. The old ConversationItem::create() here only
        // persisted the item and broadcast it to the widget — on a WhatsApp
        // conversation the "greeting" never left the database.
        app(ConversationMessageService::class)->store($conversation, [
            'body' => $body,
            'is_internal' => $isInternal,
            'user_id' => $this->resolveSenderId($conversation),
            'metadata' => ['source' => 'automation'],
        ]);
    }

    /**
     * Who an automated reply is attributed to: the conversation's assignee,
     * falling back to the inbox's configured default assignee, falling back
     * to any admin (helpdesk.manage) so the message is never left orphaned.
     */
    private function resolveSenderId(Conversation $conversation): ?int
    {
        if ($conversation->assignee_id) {
            return $conversation->assignee_id;
        }

        $inboxDefault = $conversation->inbox?->default_assignee_id;
        if ($inboxDefault) {
            return $inboxDefault;
        }

        return User::role('super-settings')->value('id');
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
