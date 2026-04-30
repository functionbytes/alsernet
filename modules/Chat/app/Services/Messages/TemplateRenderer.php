<?php

namespace Modules\Chat\Services\Messages;

use App\Models\User;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Templates\MessageTemplate;

class TemplateRenderer
{
    /**
     * Render template content with variables.
     */
    public function render(
        MessageTemplate $template,
        ?Conversation $conversation = null,
        ?Customer $customer = null,
        ?User $agent = null,
        ?Account $account = null
    ): string {
        $content = $template->content;

        // Prepare variables
        $variables = $this->prepareVariables($conversation, $customer, $agent, $account);

        // Replace variables
        foreach ($variables as $key => $value) {
            $content = str_replace('{{'.$key.'}}', $value, $content);
        }

        // Record usage
        $template->recordUsage();

        return $content;
    }

    /**
     * Replace variables in any content string.
     */
    public function renderContent(
        string $content,
        ?Conversation $conversation = null,
        ?Customer $customer = null,
        ?User $agent = null,
        ?Account $account = null
    ): string {
        // Prepare variables
        $variables = $this->prepareVariables($conversation, $customer, $agent, $account);

        // Replace variables
        foreach ($variables as $key => $value) {
            $content = str_replace('{{'.$key.'}}', $value, $content);
        }

        return $content;
    }

    /**
     * Prepare variables for replacement.
     */
    protected function prepareVariables(
        ?Conversation $conversation,
        ?Customer $customer,
        ?User $agent,
        ?Account $account
    ): array {
        $variables = [];

        // Customer variables (preferred)
        if ($customer) {
            $variables['customer.name'] = $customer->name ?? '';
            $variables['customer.email'] = $customer->email ?? '';
            $variables['customer.phone'] = $customer->phone_number ?? '';
            $variables['customer.city'] = $customer->city ?? '';
            $variables['customer.country'] = $customer->country ?? '';
            $variables['customer.id'] = $customer->id ?? '';
        }

        // Contact variables (backward compatibility - same as customer)
        if ($customer) {
            $variables['contact.name'] = $customer->name ?? '';
            $variables['contact.email'] = $customer->email ?? '';
            $variables['contact.phone'] = $customer->phone_number ?? '';
            $variables['contact.city'] = $customer->city ?? '';
            $variables['contact.country'] = $customer->country ?? '';
            $variables['contact.id'] = $customer->id ?? '';
        }

        // Agent variables
        if ($agent) {
            $variables['agent.name'] = ($agent->firstname ?? '').' '.($agent->lastname ?? '');
            $variables['agent.name'] = trim($variables['agent.name']) ?: 'Agent';
            $variables['agent.email'] = $agent->email ?? '';
            $variables['agent.firstname'] = $agent->firstname ?? '';
            $variables['agent.lastname'] = $agent->lastname ?? '';
        }

        // Conversation variables
        if ($conversation) {
            $variables['conversation.id'] = $conversation->id;
            $variables['conversation.status'] = $conversation->status?->name ?? '';
            $variables['conversation.reference'] = '#'.$conversation->id;

            // Customer info from conversation relationship
            if ($conversation->customer) {
                $variables['contact.id'] = $conversation->customer->id ?? '';
            }
        }

        // Account variables
        if ($account) {
            $variables['account.name'] = $account->name ?? '';
            $variables['account.id'] = $account->id ?? '';
        }

        // System variables
        $variables['system.date'] = now()->format('Y-m-d');
        $variables['system.time'] = now()->format('H:i:s');
        $variables['system.datetime'] = now()->format('Y-m-d H:i:s');
        $variables['system.year'] = now()->format('Y');

        return $variables;
    }

    /**
     * Get preview of rendered template.
     */
    public function preview(
        MessageTemplate $template,
        ?Conversation $conversation = null,
        ?Customer $customer = null,
        ?User $agent = null,
        ?Account $account = null
    ): string {
        $content = $template->content;

        // Prepare variables
        $variables = $this->prepareVariables($conversation, $customer, $agent, $account);

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
