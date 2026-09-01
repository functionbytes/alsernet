<?php

namespace Modules\HelpdeskCompliance\Services\Handlers;

use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Models\EmailSuppression;

/**
 * Cascades a GDPR erasure to HelpdeskEmailLog. EmailLog/EmailLogOpen/
 * EmailSuppression are not linked by customer_id — they only ever knew the
 * raw recipient address — so this handler matches by the customer's email,
 * captured BEFORE the core deletion anonymized/removed the Customer row
 * (same contract as DocumentComplianceHandler).
 *
 * EmailLog/EmailLogOpen live in the default connection (not 'helpdesk'), so —
 * same as DocumentComplianceHandler — this runs OUTSIDE the job's
 * DB::connection('helpdesk')->transaction() and must stay idempotent
 * (re-running against already-redacted/deleted rows is a no-op).
 *
 * Matching uses a LIKE on the recipients_index column (same convention as
 * EmailLogController::applyFilters() search box — see its comment on why
 * MATCH...AGAINST is too imprecise for an exact recipient match), not
 * whereJsonContains on to_addresses/cc_addresses/bcc_addresses: JSON string
 * comparison is byte-exact, and the address may have been stored with
 * different casing than Customer.email.
 *
 * EmailSuppression is intentionally NOT deleted, even in hard mode: it exists
 * specifically to survive the customer it concerns (never re-email a
 * bounced/complained/unsubscribed address) — deleting it would silently
 * re-enable sending to that address. Only the plain-text email is replaced
 * with a one-way hash so the row no longer carries PII at rest.
 *
 * EmailLog now uses SoftDeletes (the "papelera" feature, 30-day recovery
 * window for manual deletions from the panel — see EmailLogController::
 * destroy()/restore()). A GDPR erasure must NEVER be recoverable, so:
 *  - The query below uses withTrashed(): a log an agent already moved to
 *    the trash (but that has not yet hit the 30-day auto-purge) still
 *    carries the customer's PII and must not survive just because it is
 *    currently hidden from the default listing.
 *  - Hard mode calls forceDelete(), never delete(): a plain delete() on a
 *    soft-deletable model only sets deleted_at, which would leave the row
 *    (and its PII) sitting in the trash, directly contradicting the "no
 *    volver a hidratar la fila" contract with this handler.
 *
 * ALCANCE (dejado para revisión legal/producto): hashear
 * email_suppressions.email rompe el lookup por texto plano de
 * EmailSuppression::isSuppressed()/scopeCoversModule() para esa dirección —
 * actualizar ese matching a hash-contra-hash es un cambio más amplio dentro
 * de HelpdeskEmailLog (toca EnforceEmailSuppression y los puntos de escritura
 * de la lista de supresión), fuera del alcance de este handler.
 */
class EmailLogComplianceHandler
{
    /**
     * @return array{module: string, logs: int, opens: int, suppressions: int, mode: string}
     */
    public function handle(?string $customerEmail, bool $hard): array
    {
        $email = trim((string) $customerEmail);

        if ($email === '') {
            return ['module' => 'HelpdeskEmailLog', 'logs' => 0, 'opens' => 0, 'suppressions' => 0, 'mode' => 'skipped'];
        }

        $logs = 0;
        $opens = 0;
        $like = '%'.addcslashes($email, '%_\\').'%';

        EmailLog::query()
            ->withTrashed()
            ->where('recipients_index', 'like', $like)
            ->with('opens')
            ->chunkById(200, function ($chunk) use (&$logs, &$opens, $hard): void {
                foreach ($chunk as $log) {
                    $opens += $log->opens->count();

                    if ($hard) {
                        $log->opens()->delete();
                        $log->forceDelete();
                    } else {
                        $log->opens()->update(['ip' => null, 'user_agent' => null]);

                        $log->update([
                            'to_addresses' => [],
                            'cc_addresses' => null,
                            'bcc_addresses' => null,
                            'reply_to' => null,
                            'recipients_index' => null,
                            'subject' => config('helpdeskcompliance.redacted_text'),
                            'body_html' => null,
                            'body_text' => null,
                            'raw_headers' => null,
                            'attachments' => null,
                        ]);
                    }

                    $logs++;
                }
            });

        $suppressions = EmailSuppression::query()
            ->where('email', mb_strtolower($email))
            ->update(['email' => hash('sha256', mb_strtolower($email)), 'notes' => null]);

        return [
            'module' => 'HelpdeskEmailLog',
            'logs' => $logs,
            'opens' => $opens,
            'suppressions' => $suppressions,
            'mode' => $hard ? 'deleted' : 'redacted',
        ];
    }
}
