<?php

namespace Modules\Campaign\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Csv\Reader;
use Modules\Campaign\Models\CampaignMaillist;

/**
 * Importa suscriptores desde un CSV a una mailing list.
 *
 * El CSV puede tener cabeceras o no. El parámetro $mapping mapea
 * índice/nombre de columna → tag del field (EMAIL, FIRST_NAME, …).
 * Si no se pasa mapping y el CSV tiene cabeceras, se intenta auto-mapeo
 * fuzzy: la columna que contenga "email" → EMAIL, "name"/"first" → FIRST_NAME, etc.
 *
 * Procesa en batches de 500 con upsert para idempotencia.
 */
class ImportSubscribersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(
        protected int $mailListId,
        protected string $csvPath,
        protected array $mapping = [],
        protected bool $hasHeaders = true,
    ) {}

    public function handle(): void
    {
        $list = CampaignMaillist::find($this->mailListId);
        if (! $list) {
            return;
        }

        if (! is_file($this->csvPath)) {
            \Log::error('ImportSubscribersJob: archivo no existe '.$this->csvPath);

            return;
        }

        $csv = Reader::createFromPath($this->csvPath, 'r');
        if ($this->hasHeaders) {
            $csv->setHeaderOffset(0);
        }

        $headers = $this->hasHeaders ? $csv->getHeader() : [];
        $mapping = $this->normalizeMapping($mapping = $this->mapping ?: $this->autoMap($headers));

        $emailKey = array_search('EMAIL', $mapping, true);
        if ($emailKey === false) {
            \Log::error('ImportSubscribersJob: no hay columna EMAIL en el mapping');

            return;
        }

        $batch = [];
        $now = now();
        $count = 0;

        foreach ($csv->getRecords() as $row) {
            $email = trim((string) ($row[$emailKey] ?? ''));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $attributes = [];
            $first = null;
            $last = null;
            foreach ($mapping as $col => $tag) {
                $val = $row[$col] ?? null;
                if ($val === null || $val === '') {
                    continue;
                }
                match ($tag) {
                    'EMAIL' => null,
                    'FIRST_NAME' => $first = $val,
                    'LAST_NAME' => $last = $val,
                    default => $attributes[$tag] = $val,
                };
            }

            $batch[] = [
                'email' => $email,
                'first_name' => $first,
                'last_name' => $last,
                'attributes' => $attributes ? json_encode($attributes) : null,
                'subscribed_at' => $now,
            ];

            if (count($batch) >= 500) {
                $this->flush($list, $batch, $now);
                $batch = [];
            }
            $count++;
        }

        if ($batch) {
            $this->flush($list, $batch, $now);
        }

        \Log::info("ImportSubscribersJob: lista {$list->uid} importó {$count} emails desde {$this->csvPath}.");

        // Refresca el contador en cache
        $list->cached_subscriber_count = $list->subscribers()->count();
        $list->save();

        // Limpia el archivo temporal
        @unlink($this->csvPath);
    }

    /**
     * Procesa un batch: upsert en campaign_subscribers + asociación pivot.
     */
    protected function flush(CampaignMaillist $list, array $batch, $now): void
    {
        // 1) Upsert subscribers globales
        foreach ($batch as &$row) {
            if (empty($row['uid'])) {
                $row['uid'] = (string) Str::uuid();
            }
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }
        unset($row);

        DB::table('campaign_subscribers')->upsert(
            $batch,
            ['email'],
            ['first_name', 'last_name', 'attributes', 'updated_at'],
        );

        // 2) Recuperar IDs por email y asociar al pivot
        $emails = array_column($batch, 'email');
        $subs = DB::table('campaign_subscribers')
            ->whereIn('email', $emails)
            ->pluck('id', 'email');

        $pivotRows = [];
        foreach ($subs as $email => $subId) {
            $pivotRows[] = [
                'uid' => (string) Str::uuid(),
                'mail_list_id' => $list->id,
                'subscriber_id' => $subId,
                'status' => 'subscribed',
                'subscribed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('campaign_maillists_subscribers')->upsert(
            $pivotRows,
            ['mail_list_id', 'subscriber_id'],
            ['status', 'subscribed_at', 'updated_at'],
        );
    }

    /**
     * Auto-mapea cabeceras CSV a tags conocidos por nombre fuzzy.
     */
    protected function autoMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $h) {
            $lower = strtolower(trim((string) $h));
            $tag = match (true) {
                str_contains($lower, 'email'), str_contains($lower, 'correo'), str_contains($lower, 'e-mail') => 'EMAIL',
                str_contains($lower, 'first'), $lower === 'name', str_contains($lower, 'nombre') => 'FIRST_NAME',
                str_contains($lower, 'last'), str_contains($lower, 'apellido'), str_contains($lower, 'surname') => 'LAST_NAME',
                default => strtoupper($lower),
            };
            $map[$h] = $tag;
        }

        return $map;
    }

    /**
     * Normaliza el mapping convirtiendo índices numéricos en strings.
     */
    protected function normalizeMapping(array $mapping): array
    {
        $out = [];
        foreach ($mapping as $k => $v) {
            $out[is_int($k) ? $k : (string) $k] = strtoupper((string) $v);
        }

        return $out;
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('ImportSubscribersJob failed', [
            'mail_list_id' => $this->mailListId,
            'error' => $exception->getMessage(),
        ]);

        // tries=1: sin reintento, así que limpiar aquí evita dejar el CSV
        // temporal huérfano en disco (unlink() antes solo corría al éxito).
        @unlink($this->csvPath);
    }
}
