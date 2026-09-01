<?php

namespace Modules\HelpdeskEmailLog\Services;

use Illuminate\Support\Facades\Auth;
use Modules\HelpdeskEmailLog\Enums\SuppressionReason;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Models\EmailSuppression;

/**
 * Único punto de escritura de la lista de supresión — lo usan tanto el
 * controlador de gestión manual (Settings) como EmailLogObserver
 * (auto-alimentación desde bounces/quejas). Centraliza el dedup (email,
 * module) y deja rastro de quién/qué la disparó.
 */
class EmailSuppressionService
{
    /**
     * Añade (o actualiza el motivo de) una supresión. Idempotente: si ya
     * existe la misma combinación email+module, actualiza el motivo/notas
     * en vez de duplicar (el índice único de la tabla lo exige igualmente).
     */
    public function suppress(
        string $email,
        SuppressionReason $reason,
        ?string $module = null,
        ?EmailLog $emailLog = null,
        ?string $notes = null,
        bool $automatic = false,
    ): EmailSuppression {
        $user = $automatic ? null : Auth::user();

        return EmailSuppression::updateOrCreate(
            ['email' => mb_strtolower(trim($email)), 'module' => $module ?? ''],
            [
                'reason' => $reason,
                'email_log_id' => $emailLog?->id,
                'notes' => $notes,
                'causer_id' => $user?->id,
                'causer_type' => $user ? $user::class : null,
            ],
        );
    }

    public function unsuppress(int $id): void
    {
        EmailSuppression::whereKey($id)->delete();
    }

    public function isSuppressed(string $email, ?string $module = null): bool
    {
        return EmailSuppression::isSuppressed($email, $module);
    }
}
