<?php

namespace Modules\Helpdesk\Services\Templates;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Keepsuit\Liquid\Environment;
use Keepsuit\Liquid\EnvironmentFactory;
use Keepsuit\Liquid\Exceptions\LiquidException;
use Keepsuit\Liquid\Template;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\Templates\Drops\AgentDrop;
use Modules\Helpdesk\Services\Templates\Drops\ContactDrop;
use Modules\Helpdesk\Services\Templates\Drops\ConversationDrop;
use Modules\Helpdesk\Services\Templates\Drops\InboxDrop;
use Throwable;

class LiquidRenderer
{
    private Environment $environment;

    /** @var array<string, Template> */
    private array $memoryCache = [];

    public function __construct()
    {
        $this->environment = EnvironmentFactory::new()
            ->setRethrowErrors(false)
            ->build();
    }

    /**
     * Render a Liquid template string with the given context data.
     * On failure, returns the original template and logs the error.
     */
    public function render(string $template, array $context): string
    {
        try {
            $parsed = $this->getParsedTemplate($template);
            $renderContext = $this->environment->newRenderContext(data: $context);

            return $parsed->render($renderContext);
        } catch (Throwable $e) {
            Log::warning('LiquidRenderer: render failed', [
                'error' => $e->getMessage(),
                'template_preview' => mb_substr($template, 0, 200),
            ]);

            return $template;
        }
    }

    /**
     * Validate a Liquid template string.
     *
     * @return array{valid: bool, errors: string[]}
     */
    public function isValid(string $template): array
    {
        try {
            $this->environment->parseString($template);

            return ['valid' => true, 'errors' => []];
        } catch (LiquidException $e) {
            return ['valid' => false, 'errors' => [$e->getMessage()]];
        } catch (Throwable $e) {
            return ['valid' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * List all variable names available when rendering for a conversation.
     * Used for autocomplete in the UI.
     *
     * @return array<string, array<string>>
     */
    public function availableVariables(): array
    {
        return [
            'contact' => ['id', 'name', 'email', 'phone', 'company', 'created_at', 'custom_attributes'],
            'conversation' => ['id', 'subject', 'status', 'priority', 'channel', 'created_at', 'assignee', 'inbox'],
            'conversation.assignee' => ['id', 'firstname', 'lastname', 'name', 'email'],
            'conversation.inbox' => ['id', 'name', 'channel_type'],
            'inbox' => ['id', 'name', 'channel_type'],
            'agent' => ['id', 'firstname', 'lastname', 'name', 'email'],
        ];
    }

    /**
     * Build a context array for a conversation and render the template.
     */
    public function renderForConversation(string $template, Conversation $conversation, array $extra = []): string
    {
        $context = [
            'contact' => $conversation->customer ? new ContactDrop($conversation->customer) : null,
            'conversation' => new ConversationDrop($conversation),
            'inbox' => $conversation->inbox ? new InboxDrop($conversation->inbox) : null,
            'agent' => $conversation->assignee ? new AgentDrop($conversation->assignee) : null,
            ...$extra,
        ];

        return $this->render($template, $context);
    }

    /**
     * Get a parsed Template.
     * Uses an in-process memory cache first, then falls back to Redis for
     * persistence across requests. Keyed by MD5 of the template string.
     */
    private function getParsedTemplate(string $template): Template
    {
        $hash = md5($template);

        if (isset($this->memoryCache[$hash])) {
            return $this->memoryCache[$hash];
        }

        $parsed = Cache::remember(
            "liquid:tpl:{$hash}",
            3600,
            fn () => $this->environment->parseString($template)
        );

        return $this->memoryCache[$hash] = $parsed;
    }
}
