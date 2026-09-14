<?php

namespace Modules\HelpdeskAgents\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Modules\HelpdeskAgents\Models\AiAgent;

/**
 * SEC-09 (2026-08 audit): AiAgent::getApiKey() falls back to three legacy
 * plaintext origins — a value written to `api_key_encrypted` before the
 * `encrypted` cast existed on that column (decrypt fails, raw value used),
 * `parameters['api_key']` and `backups['api_key']`. None of the module's
 * migrations re-encrypt those rows (one older migration,
 * 2026_04_19_000009_migrate_api_key_to_encrypted_column_on_helpdesk_ai_agents,
 * only ever covered `backups['api_key']`; it already ran and can't be
 * re-triggered, and never touched `parameters` or a legacy plaintext
 * `api_key_encrypted`).
 *
 * One-shot, idempotent: resolves the real key the same way getApiKey()
 * does, persists it through the model's `encrypted` cast, and strips the
 * plaintext leftovers. A row that is already clean (valid ciphertext in
 * api_key_encrypted, nothing left in parameters/backups) is reported as
 * "already clean" and untouched on a second run.
 *
 * Does NOT touch AiAgent::getApiKey()'s fallbacks — those stay until this
 * has been verified against production data (out of scope here).
 */
class EncryptAiAgentApiKeys extends Command
{
    protected $signature = 'helpdesk:agents:encrypt-api-keys
        {--dry-run : No persiste ningun cambio, solo reporta cuantas filas se verian afectadas}';

    protected $description = 'Re-cifra las claves de API de helpdesk_ai_agents que aun viven en claro (api_key_encrypted legacy, parameters.api_key, backups.api_key)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $affected = 0;
        $alreadyClean = 0;
        $skippedNoKey = 0;

        AiAgent::withTrashed()->get()->each(function (AiAgent $agent) use (&$affected, &$alreadyClean, &$skippedNoKey, $dryRun): void {
            $needsWrite = false;
            $resolvedKey = null;

            // 1. Legacy plaintext sitting directly in api_key_encrypted,
            //    written before the 'encrypted' cast existed on the column.
            try {
                $decrypted = $agent->api_key_encrypted; // triggers cast decrypt
                if ($decrypted !== null) {
                    $resolvedKey = $decrypted;
                }
            } catch (DecryptException) {
                $resolvedKey = $agent->getRawOriginal('api_key_encrypted');
                $needsWrite = $resolvedKey !== null;
            }

            $parameters = $agent->parameters ?? [];
            $backups = $agent->backups ?? [];

            // 2/3. api_key still parked in parameters/backups — resolved as
            //      last resort if api_key_encrypted had nothing, and always
            //      flagged for cleanup even when api_key_encrypted was
            //      already fine (belt & braces: strips leftover duplicates
            //      the 2026_04_19_000009 migration didn't reach).
            if ($resolvedKey === null && ! empty($parameters['api_key'])) {
                $resolvedKey = $parameters['api_key'];
                $needsWrite = true;
            }

            if ($resolvedKey === null && ! empty($backups['api_key'])) {
                $resolvedKey = $backups['api_key'];
                $needsWrite = true;
            }

            $hasLeftoverPlaintext = ! empty($parameters['api_key']) || ! empty($backups['api_key']);

            if ($resolvedKey === null) {
                $skippedNoKey++;

                return;
            }

            if (! $needsWrite && ! $hasLeftoverPlaintext) {
                $alreadyClean++;

                return;
            }

            $affected++;

            if ($dryRun) {
                return;
            }

            $agent->api_key_encrypted = $resolvedKey;

            if (! empty($parameters['api_key'])) {
                unset($parameters['api_key']);
                $agent->parameters = $parameters;
            }

            if (! empty($backups['api_key'])) {
                unset($backups['api_key']);
                $agent->backups = $backups;
            }

            $agent->saveQuietly();
        });

        $this->table(
            ['Estado', 'Filas'],
            [
                [$dryRun ? 'Serian re-cifradas/limpiadas' : 'Re-cifradas/limpiadas', $affected],
                ['Ya limpias (sin cambios)', $alreadyClean],
                ['Sin clave en ningun origen', $skippedNoKey],
            ]
        );

        if ($dryRun) {
            $this->warn("DRY-RUN: no se persistio ningun cambio. {$affected} fila(s) se verian afectadas.");
        } else {
            $this->info("Hecho: {$affected} fila(s) re-cifradas/limpiadas.");
        }

        return self::SUCCESS;
    }
}
