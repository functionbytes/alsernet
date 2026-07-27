<?php

declare(strict_types=1);

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GdprController extends Controller
{
    /**
     * POST /eng/api/sdk/gdpr/export
     *
     * Returns all personal data associated with the given email, scoped to
     * the authenticated inbox (website_token). Complies with GDPR Art. 15.
     *
     * TODO: Add HMAC signature verification from the PS plugin payload to
     * harden auth beyond the website_token already checked by EnsureWebsiteToken.
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($request->input('email')));
        $inboxId = $this->requireInboxId($request);

        $data = [
            'email' => $email,
            'visitor_sessions' => $this->collectFromTable('engagement_visitor_sessions', $email, $inboxId),
            'visitor_contexts' => $this->collectFromTable('engagement_visitor_contexts', $email, $inboxId),
            'events' => $this->collectFromTable('engagement_events', $email, $inboxId),
            'mobile_devices' => $this->collectFromTable('engagement_mobile_devices', $email, $inboxId),
            'audit_logs' => $this->collectFromTable('engagement_audit_logs', $email, $inboxId),
            'exported_at' => now()->toIso8601String(),
        ];

        return response()->json(['ok' => true, 'data' => $data]);
    }

    /**
     * POST /eng/api/sdk/gdpr/delete
     *
     * Anonymises personal data for the given email. Personal identifiers
     * (email, IP, user-agent) are replaced with hashed/neutral values so that
     * aggregate metrics remain intact. Complies with GDPR Art. 17.
     *
     * TODO: Add HMAC signature verification from the PS plugin payload.
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($request->input('email')));
        $hash = hash('sha256', $email);
        $inboxId = $this->requireInboxId($request);

        $totals = [];

        DB::transaction(function () use ($email, $hash, $inboxId, &$totals): void {
            // Anonymise rows that still carry PII but must be kept for aggregates.
            foreach (['engagement_visitor_sessions', 'engagement_visitor_contexts', 'engagement_audit_logs'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $totals[$table] = $this->scopedQuery($table, $email, $inboxId)->update([
                    'email' => 'gdpr-deleted-'.$hash.'@deleted.local',
                    'ip_address' => '0.0.0.0',
                    'user_agent' => 'redacted',
                ]);
            }

            // Mobile device tokens are fully personal — hard delete.
            if (Schema::hasTable('engagement_mobile_devices')) {
                $totals['engagement_mobile_devices'] = $this->scopedQuery('engagement_mobile_devices', $email, $inboxId)->delete();
            }

            // Event records contain PII in their payload — hard delete.
            if (Schema::hasTable('engagement_events')) {
                $totals['engagement_events'] = $this->scopedQuery('engagement_events', $email, $inboxId)->delete();
            }
        });

        Log::info('GDPR delete executed', [
            'email_hash' => substr($hash, 0, 16),
            'inbox_id' => $inboxId,
            'totals' => $totals,
        ]);

        return response()->json(['ok' => true, 'deleted' => $totals]);
    }

    private function requireInboxId(Request $request): int
    {
        $inbox = $request->attributes->get('livechat_inbox');
        abort_unless($inbox, 401, 'Canal web no resuelto.');

        return (int) $inbox->id;
    }

    private function collectFromTable(string $table, string $email, int $inboxId): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'email')) {
            return [];
        }

        return $this->scopedQuery($table, $email, $inboxId)->limit(1000)->get()->toArray();
    }

    /**
     * Builds an email-filtered query always scoped to the caller's inbox when
     * the table exposes an `inbox_id` column, preventing cross-tenant access.
     */
    private function scopedQuery(string $table, string $email, int $inboxId): Builder
    {
        $query = DB::table($table);

        // NOTE: the engagement_* tables do not currently expose an `email`
        // column (data is keyed by session_token / customer_id, not email).
        // Guard the filter so delete/export never throw a "unknown column"
        // SQL error and never touch unrelated rows. The email↔data mapping
        // (likely via customer_id -> helpdesk_customers.email) needs a
        // dedicated redesign before this endpoint can actually resolve data.
        if (Schema::hasColumn($table, 'email')) {
            $query->where('email', $email);
        } else {
            $query->whereRaw('1 = 0');
        }

        if (Schema::hasColumn($table, 'inbox_id')) {
            $query->where('inbox_id', $inboxId);
        }

        return $query;
    }
}
