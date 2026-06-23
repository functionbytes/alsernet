<?php

namespace Modules\HelpdeskCompliance\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\Helpdesk\Services\AuditLogService;
use Modules\HelpdeskCompliance\Models\ComplianceRequest;
use Modules\HelpdeskCompliance\Services\Handlers\ChatflowComplianceHandler;
use Modules\HelpdeskCompliance\Services\Handlers\TicketComplianceHandler;
use Nwidart\Modules\Facades\Module;

/**
 * Runs synchronously after a core GDPR deletion to cascade the erasure to the
 * modules the core does not cover, recording a trazable ComplianceRequest.
 *
 * Each handler is gated by Module::isEnabled() + class_exists() so the cascade
 * degrades gracefully when an optional module is absent or disabled.
 */
class RunComplianceCascade
{
    public function handle(CustomerGdprDeleted $event): void
    {
        $customer = $event->customer;
        $summaries = [];

        DB::connection('helpdesk')->transaction(function () use ($event, $customer, &$summaries): void {
            if ($this->moduleReady('HelpdeskTickets', 'Modules\\HelpdeskTickets\\Models\\Ticket')) {
                $summaries[] = app(TicketComplianceHandler::class)
                    ->handle($customer, $event->hard, $event->conversationIds);
            }

            if ($this->moduleReady('HelpdeskChatFlow', 'Modules\\HelpdeskChatFlow\\Models\\ChatFlowSession')) {
                $summaries[] = app(ChatflowComplianceHandler::class)
                    ->handle($customer, $event->hard, $event->conversationIds);
            }
        });

        $request = ComplianceRequest::create([
            'customer_id' => $customer->id,
            'type' => $event->hard ? ComplianceRequest::TYPE_DELETE_HARD : ComplianceRequest::TYPE_DELETE_SOFT,
            'status' => 'completed',
            'requested_by' => auth()->id(),
            'modules_affected' => array_values(array_map(fn (array $s): string => $s['module'], $summaries)),
            'result_summary' => [
                'core' => $event->result,
                'handlers' => $summaries,
            ],
            'completed_at' => now(),
        ]);

        AuditLogService::record('gdpr.cascade.completed', $request, [], [
            'mode' => $event->hard ? 'hard' : 'soft',
            'handlers' => $summaries,
        ]);
    }

    private function moduleReady(string $module, string $fqcn): bool
    {
        return (Module::find($module)?->isEnabled() ?? false) && class_exists($fqcn);
    }
}
