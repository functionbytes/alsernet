<?php

namespace Modules\Helpdesk\Services\Automation\Contracts;

interface AutomationAction
{
    /**
     * Execute the action with the given params and context.
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $context
     */
    public function execute(array $params, array $context): void;

    /**
     * The unique type identifier used in the action config JSON.
     */
    public static function actionType(): string;

    /**
     * JSON schema describing the params accepted by this action (used by UI).
     *
     * @return array<string, mixed>
     */
    public static function paramSchema(): array;
}
