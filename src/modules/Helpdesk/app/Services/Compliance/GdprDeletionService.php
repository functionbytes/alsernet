<?php

namespace Modules\Helpdesk\Services\Compliance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\AuditLogService;

class GdprDeletionService
{
    private const REDACTED_BODY = '[Contenido eliminado por solicitud GDPR]';

    /**
     * Process a GDPR deletion request for the given customer.
     *
     * Soft delete (default): anonymises PII but retains statistical data.
     * Hard delete: cascades all records permanently.
     *
     * @return array{deleted: int, anonymized: int}
     */
    public function deleteCustomer(Customer $customer, bool $hard = false): array
    {
        AuditLogService::record('gdpr.deletion.requested', $customer, [], [
            'mode' => $hard ? 'hard' : 'soft',
            'requested_by' => auth()->id(),
        ]);

        // Capture conversation ids before deletion so listeners can cascade the
        // erasure to related records even after a hard delete removes them.
        $conversationIds = $customer->conversations()->withTrashed()->pluck('id')->all();

        $result = $hard ? $this->hardDelete($customer) : $this->softDelete($customer);

        CustomerGdprDeleted::dispatch($customer, $hard, $conversationIds, $result);

        return $result;
    }

    /**
     * @return array{deleted: int, anonymized: int}
     */
    private function softDelete(Customer $customer): array
    {
        $anonymized = 0;
        $deleted = 0;

        DB::connection('helpdesk')->transaction(function () use ($customer, &$anonymized, &$deleted) {
            // Anonymise customer PII
            $customer->update([
                'name' => 'Cliente eliminado #'.$customer->id,
                'email' => null,
                'phone' => null,
                'whatsapp_phone' => null,
                'facebook_psid' => null,
                'instagram_id' => null,
                'avatar_url' => null,
                'internal_notes' => null,
                'custom_attributes' => null,
                'portal_token' => null,
                'portal_password' => null,
            ]);
            $anonymized++;

            // Redact conversation item bodies and remove attachments
            $conversationIds = $customer->conversations()->pluck('id');

            ConversationItem::query()
                ->whereIn('conversation_id', $conversationIds)
                ->whereNull('deleted_at')
                ->chunkById(200, function ($items) use (&$deleted) {
                    foreach ($items as $item) {
                        $this->deleteAttachmentFiles($item->attachment_urls ?? []);

                        $item->update([
                            'body' => self::REDACTED_BODY,
                            'html_body' => null,
                            'attachment_urls' => [],
                        ]);
                        $deleted++;
                    }
                });

            // Soft-delete the customer record for the 90-day retention window
            $customer->delete();
        });

        return ['deleted' => $deleted, 'anonymized' => $anonymized];
    }

    /**
     * @return array{deleted: int, anonymized: int}
     */
    private function hardDelete(Customer $customer): array
    {
        $deleted = 0;

        DB::connection('helpdesk')->transaction(function () use ($customer, &$deleted) {
            $conversationIds = $customer->conversations()->withTrashed()->pluck('id');

            // Delete all conversation items (including soft-deleted)
            $items = ConversationItem::query()
                ->withTrashed()
                ->whereIn('conversation_id', $conversationIds)
                ->get();

            foreach ($items as $item) {
                $this->deleteAttachmentFiles($item->attachment_urls ?? []);
                $item->forceDelete();
                $deleted++;
            }

            // Delete csat and nps ratings
            DB::connection('helpdesk')
                ->table('helpdesk_csat_ratings')
                ->where('customer_id', $customer->id)
                ->delete();

            DB::connection('helpdesk')
                ->table('helpdesk_nps_ratings')
                ->where('customer_id', $customer->id)
                ->delete();

            // Delete drip executions
            DB::connection('helpdesk')
                ->table('helpdesk_drip_executions')
                ->where('customer_id', $customer->id)
                ->delete();

            // Delete conversations
            Conversation::query()
                ->withTrashed()
                ->whereIn('id', $conversationIds)
                ->forceDelete();
            $deleted += $conversationIds->count();

            // Hard-delete the customer
            $customer->forceDelete();
            $deleted++;
        });

        return ['deleted' => $deleted, 'anonymized' => 0];
    }

    /**
     * Remove physical attachment files from storage.
     *
     * @param  array<string>  $attachmentUrls
     */
    private function deleteAttachmentFiles(array $attachmentUrls): void
    {
        foreach ($attachmentUrls as $url) {
            $path = $this->urlToStoragePath($url);

            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function urlToStoragePath(mixed $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $publicPath = parse_url($url, PHP_URL_PATH);
        if (! $publicPath) {
            return null;
        }

        $relativePath = preg_replace('#^/storage/#', '', $publicPath);

        return $relativePath ?: null;
    }
}
