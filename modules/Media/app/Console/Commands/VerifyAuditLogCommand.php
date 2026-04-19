<?php

namespace Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyAuditLogCommand extends Command
{
    protected $signature = 'media:verify-audit-log';

    protected $description = 'Verifica la integridad HMAC de los logs de Media';

    public function handle(): int
    {
        $rows = DB::table('activity_log')
            ->where('log_name', 'like', 'media.%')
            ->whereNotNull('hmac_signature')
            ->cursor();

        $ok = 0;
        $tampered = [];

        foreach ($rows as $row) {
            $payload = [
                'log_name' => $row->log_name,
                'description' => $row->description,
                'subject_type' => $row->subject_type,
                'subject_id' => $row->subject_id,
                'causer_type' => $row->causer_type,
                'causer_id' => $row->causer_id,
                'properties' => $row->properties,
                'created_at' => (string) $row->created_at,
            ];

            $expected = hash_hmac('sha256', json_encode($payload, JSON_SORT_KEYS), config('app.key'));

            if (hash_equals($expected, $row->hmac_signature)) {
                $ok++;
            } else {
                $tampered[] = $row->id;
            }
        }

        $this->info("Verificación completa: {$ok} OK, ".count($tampered).' tampered');

        if (! empty($tampered)) {
            $this->error('IDs alterados: '.implode(',', array_slice($tampered, 0, 20)));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
