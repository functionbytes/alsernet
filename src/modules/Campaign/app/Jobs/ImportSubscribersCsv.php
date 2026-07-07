<?php

namespace Modules\Campaign\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\Csv\Reader;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSubscriber;

/**
 * Importa suscriptores desde CSV en chunks.
 * Mapeo de columnas viene en $mapping: ['email' => 0, 'first_name' => 1, ...]
 */
class ImportSubscribersCsv implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 300;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $maillistId,
        public readonly string $filePath,
        public readonly array $mapping,
        public readonly bool $skipInvalid = true,
        public readonly ?int $initiatedByUserId = null,
    ) {}

    public function handle(): void
    {
        $list = CampaignMaillist::find($this->maillistId);
        if (! $list) {
            Log::error('Import CSV: lista no encontrada', ['maillist_id' => $this->maillistId]);

            return;
        }

        $reader = Reader::createFromPath($this->filePath, 'r');
        $reader->setHeaderOffset(0);
        $records = $reader->getRecords();

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($records as $offset => $row) {
            $email = $this->extractValue($row, $this->mapping['email'] ?? 'email');
            $email = CampaignSubscriber::normalizeEmail(trim((string) $email));

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                if (! $this->skipInvalid) {
                    $errors[] = "Fila {$offset}: email inválido ({$email})";
                }

                continue;
            }

            $firstName = $this->extractValue($row, $this->mapping['first_name'] ?? null);
            $lastName = $this->extractValue($row, $this->mapping['last_name'] ?? null);

            DB::transaction(function () use ($email, $firstName, $lastName, $list, &$created): void {
                $subscriber = CampaignSubscriber::firstOrCreate(
                    ['email' => $email],
                    [
                        'uid' => (string) Str::uuid(),
                        'first_name' => $firstName ?: null,
                        'last_name' => $lastName ?: null,
                        'source' => 'csv_import',
                        'subscribed_at' => now(),
                    ]
                );

                DB::table('campaign_maillists_subscribers')->updateOrInsert(
                    ['mail_list_id' => $list->id, 'subscriber_id' => $subscriber->id],
                    [
                        'uid' => (string) Str::uuid(),
                        'status' => 'subscribed',
                        'subscribed_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $created++;
            });
        }

        Log::info('Import CSV completado', [
            'maillist_id' => $this->maillistId,
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);

        @unlink($this->filePath);
    }

    protected function extractValue(array $row, ?string $key): mixed
    {
        if ($key === null) {
            return null;
        }

        return $row[$key] ?? null;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportSubscribersCsv failed', [
            'maillist_id' => $this->maillistId,
            'error' => $exception->getMessage(),
        ]);

        // Solo se llega aquí tras agotar los reintentos (el fichero sigue
        // existiendo porque unlink() solo corre al completar con éxito):
        // limpiar para no dejar temporales huérfanos en disco.
        @unlink($this->filePath);
    }
}
