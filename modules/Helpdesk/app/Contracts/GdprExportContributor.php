<?php

namespace Modules\Helpdesk\Contracts;

use Modules\Helpdesk\Models\Customer;

/**
 * Extension point for the GDPR right-of-access export (GdprExportService).
 *
 * The core export only knows about core data (conversations, CSAT/NPS, drip,
 * audit, tags). Satellite modules holding customer PII (tickets, chatbot
 * sessions, KYC expedientes, ...) contribute their own section by implementing
 * this contract and tagging the implementation in their ServiceProvider:
 *
 *     $this->app->tag([TicketGdprExportContributor::class], GdprExportContributor::TAG);
 *
 * The tag mirrors how the deletion cascade decouples via the
 * CustomerGdprDeleted event: the core never takes a hard dependency on the
 * satellite modules, and a disabled module (whose provider returns early in
 * boot()) simply never registers its contributor.
 */
interface GdprExportContributor
{
    public const TAG = 'helpdesk.gdpr.export-contributors';

    /**
     * Key under which the section appears in the export JSON. Must be unique
     * across contributors (e.g. 'tickets', 'chatflow_sessions', 'documents').
     */
    public function sectionKey(): string;

    /**
     * All data the module holds about the customer, as a JSON-serializable
     * array. Must be complete: this feeds a legal right-of-access response.
     *
     * @return array<int|string, mixed>
     */
    public function export(Customer $customer): array;
}
