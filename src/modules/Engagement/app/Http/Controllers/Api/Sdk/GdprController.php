<?php

declare(strict_types=1);

namespace Modules\Engagement\Http\Controllers\Api\Sdk;

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
     * the authenticated channel (website_token). Complies with GDPR Art. 15.
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
        $channel = $request->attributes->get('website_channel');

        $data = [
            'email' => $email,
            'visitor_sessions' => $this->collectFromTable('engagement_visitor_sessions', $email, $channel),
            'visitor_contexts' => $this->collectFromTable('engagement_visitor_contexts', $email, $channel),
            'events' => $this->collectFromTable('engagement_events', $email, $channel),
            'mobile_devices' => $this->collectFromTable('engagement_mobile_devices', $email, $channel),
            'audit_logs' => $this->collectFromTable('engagement_audit_logs', $email, $channel),
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
        $channel = $request->attributes->get('website_channel');
        $channelId = $channel?->id;

        $totals = [];

        DB::transaction(function () use ($email, $hash, $channelId, &$totals): void {
            // Anonymise rows that still carry PII but must be kept for aggregates.
            foreach (['engagement_visitor_sessions', 'engagement_visitor_contexts', 'engagement_audit_logs'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $query = DB::table($table)->where('email', $email);

                if ($channelId && Schema::hasColumn($table, 'web_channel_id')) {
                    $query->where('web_channel_id', $channelId);
                }

                $totals[$table] = $query->update([
                    'email' => 'gdpr-deleted-'.$hash.'@deleted.local',
                    'ip_address' => '0.0.0.0',
                    'user_agent' => 'redacted',
                ]);
            }

            // Mobile device tokens are fully personal — hard delete.
            if (Schema::hasTable('engagement_mobile_devices')) {
                $totals['engagement_mobile_devices'] = DB::table('engagement_mobile_devices')
                    ->where('email', $email)
                    ->delete();
            }

            // Event records contain PII in their payload — hard delete.
            if (Schema::hasTable('engagement_events')) {
                $totals['engagement_events'] = DB::table('engagement_events')
                    ->where('email', $email)
                    ->delete();
            }
        });

        Log::info('GDPR delete executed', [
            'email_hash' => substr($hash, 0, 16),
            'channel_id' => $channelId,
            'totals' => $totals,
        ]);

        return response()->json(['ok' => true, 'deleted' => $totals]);
    }

    private function collectFromTable(string $table, string $email, ?object $channel): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'email')) {
            return [];
        }

        $query = DB::table($table)->where('email', $email);

        if ($channel && Schema::hasColumn($table, 'web_channel_id')) {
            $query->where('web_channel_id', $channel->id);
        }

        return $query->limit(1000)->get()->toArray();
    }
}
