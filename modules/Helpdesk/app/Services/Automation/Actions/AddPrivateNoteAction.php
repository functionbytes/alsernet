<?php

namespace Modules\Helpdesk\Services\Automation\Actions;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\Automation\Contracts\AutomationAction;
use Modules\Helpdesk\Services\Templates\LiquidRenderer;

class AddPrivateNoteAction implements AutomationAction
{
    public static function actionType(): string
    {
        return 'add_private_note';
    }

    public static function paramSchema(): array
    {
        return [
            'note' => ['type' => 'string', 'required' => true, 'description' => 'Contenido de la nota interna (soporta Liquid templates)'],
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

        $note = $params['note'] ?? '';

        if (blank($note)) {
            return;
        }

        $note = $this->renderTemplate($note, $conversation);

        ConversationItem::create([
            'conversation_id' => $conversation->id,
            'type' => 'internal_note',
            'body' => $note,
            'is_internal' => true,
            'metadata' => ['source' => 'automation'],
        ]);
    }

    private function renderTemplate(string $note, Conversation $conversation): string
    {
        $rendererClass = LiquidRenderer::class;

        if (class_exists($rendererClass)) {
            try {
                return app($rendererClass)->renderForConversation($note, $conversation);
            } catch (\Throwable $e) {
                Log::warning('AddPrivateNoteAction: LiquidRenderer failed, using raw note', ['error' => $e->getMessage()]);
            }
        }

        return $note;
    }
}
