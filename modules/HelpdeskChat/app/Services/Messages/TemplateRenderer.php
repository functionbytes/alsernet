<?php

namespace Modules\HelpdeskChat\Services\Messages;

use App\Models\Account;
use App\Models\MessageTemplate;
use App\Models\User;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class TemplateRenderer
{
    /**
     * Render template content with variables.
     */
    public function render(
        MessageTemplate $template,
        ?Conversation $conversation = null,
        ?Contact $contact = null,
        ?User $agent = null,
        ?Account $account = null
    ): string {
        $content = $template->content;

        // Prepare variables
        $variables = $this->prepareVariables($conversation, $contact, $agent, $account);

        // Replace variables
        foreach ($variables as $key => $value) {
            $content = str_replace('{{'.$key.'}}', $value, $content);
        }

        // Record usage
        $template->recordUsage();

        return $content;
    }

    /**
     * Prepare variables for replacement.
     */
    protected function prepareVariables(
        ?Conversation $conversation,
        ?Contact $contact,
        ?User $agent,
        ?Account $account
    ): array {
        $variables = [];

        // Contact variables
        if ($contact) {
            $variables['contact.name'] = $contact->name ?? '';
            $variables['contact.email'] = $contact->email ?? '';
            $variables['contact.phone'] = $contact->phone_number ?? '';
            $variables['contact.city'] = $contact->city ?? '';
            $variables['contact.country'] = $contact->country ?? '';
        }

        // Agent variables
        if ($agent) {
            $variables['agent.name'] = $agent->name ?? '';
            $variables['agent.email'] = $agent->email ?? '';
        }

        // Conversation variables
        if ($conversation) {
            $variables['conversation.id'] = $conversation->id;
            $variables['conversation.status'] = $conversation->status ?? '';
        }

        // Account variables
        if ($account) {
            $variables['account.name'] = $account->name ?? '';
        }

        // System variables
        $variables['system.date'] = now()->format('Y-m-d');
        $variables['system.time'] = now()->format('H:i:s');
        $variables['system.datetime'] = now()->format('Y-m-d H:i:s');

        return $variables;
    }

    /**
     * Get preview of rendered template.
     */
    public function preview(
        MessageTemplate $template,
        ?Conversation $conversation = null,
        ?Contact $contact = null,
        ?User $agent = null,
        ?Account $account = null
    ): string {
        $content = $template->content;

        // Prepare variables
        $variables = $this->prepareVariables($conversation, $contact, $agent, $account);

        // Replace variables with preview values
        foreach ($variables as $key => $value) {
            $previewValue = $value ?: '{{'.$key.'}}';
            $content = str_replace('{{'.$key.'}}', $previewValue, $content);
        }

        // Don't record usage for preview
        return $content;
    }

    /**
     * Get list of variables used in template.
     */
    public function getUsedVariables(MessageTemplate $template): array
    {
        $content = $template->content;
        $used = [];

        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);

        if (! empty($matches[1])) {
            $used = array_unique($matches[1]);
        }

        return $used;
    }

    /**
     * Validate template syntax.
     */
    public function validate(string $content): array
    {
        $errors = [];

        // Check for unmatched braces
        $openBraces = substr_count($content, '{{');
        $closeBraces = substr_count($content, '}}');

        if ($openBraces !== $closeBraces) {
            $errors[] = 'Unmatched template braces';
        }

        // Check for invalid variables
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);

        $availableVariables = array_keys(
            collect(MessageTemplate::getAvailableVariables())
                ->flatMap(fn ($vars, $group) => collect($vars)->mapWithKeys(fn ($label, $key) => [$group.'.'.$key => $label]))
                ->toArray()
        );

        if (! empty($matches[1])) {
            foreach ($matches[1] as $variable) {
                if (! in_array($variable, $availableVariables)) {
                    $errors[] = 'Unknown variable: {{'.$variable.'}}';
                }
            }
        }

        return $errors;
    }
}
