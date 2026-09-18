<?php

namespace Modules\HelpdeskSocial\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'helpdesk-social:import-comments')]
class ImportSocialCommentsCommand extends Command
{
    protected $signature = 'helpdesk-social:import-comments
                            {file : Ruta al archivo a importar}
                            {--format= : Formato del archivo (csv, json). Auto-detecta por extension si no se indica}
                            {--account= : ID de la cuenta social para asociar todos los comentarios}
                            {--dry-run : Muestra vista previa sin guardar}
                            {--skip-existing : Omite filas donde external_comment_id ya existe}';

    protected $description = 'Importa comentarios sociales desde CSV o JSON';

    /** @var array<int, array{row: int, reason: string}> */
    private array $failures = [];

    private int $imported = 0;

    private int $skipped = 0;

    private int $failed = 0;

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (! is_readable($filePath)) {
            $this->error("El archivo no existe o no es legible: {$filePath}");

            return self::FAILURE;
        }

        $format = $this->resolveFormat($filePath);

        if ($format === null) {
            $this->error('No se pudo determinar el formato del archivo. Use --format=csv o --format=json.');

            return self::FAILURE;
        }

        $accountId = $this->option('account');
        $account = null;

        if ($accountId !== null) {
            $account = SocialAccount::find($accountId);

            if ($account === null) {
                $this->error("Cuenta social #{$accountId} no encontrada.");

                return self::FAILURE;
            }
        }

        $rows = match ($format) {
            'csv' => $this->parseCsv($filePath),
            'json' => $this->parseJson($filePath),
        };

        if ($rows->isEmpty()) {
            $this->warn('No se encontraron filas para importar.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            return $this->runDryRun($rows, $account);
        }

        return $this->runImport($rows, $account);
    }

    private function resolveFormat(string $filePath): ?string
    {
        $explicit = $this->option('format');

        if ($explicit !== null) {
            $format = strtolower($explicit);

            return in_array($format, ['csv', 'json'], true) ? $format : null;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return in_array($extension, ['csv', 'json'], true) ? $extension : null;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function parseCsv(string $filePath): Collection
    {
        $reader = Reader::createFromPath($filePath, 'r');
        $reader->setHeaderOffset(0);
        $headers = $reader->getHeader();
        $required = ['external_comment_id', 'platform', 'body', 'posted_at'];
        $missing = array_diff($required, $headers);

        if (! empty($missing)) {
            $this->error('Columnas requeridas faltantes en CSV: '.implode(', ', $missing));

            return collect();
        }

        $rows = [];
        foreach ($reader->getRecords() as $index => $record) {
            $row = [];
            foreach ($record as $key => $value) {
                $row[$key] = $value !== '' ? $value : null;
            }
            $row['_row'] = $index + 2; // +2 because header is row 1
            $rows[] = $row;
        }

        return collect($rows);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function parseJson(string $filePath): Collection
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            $this->error('No se pudo leer el archivo JSON.');

            return collect();
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            $this->error('El archivo JSON no contiene un array válido.');

            return collect();
        }

        $required = ['external_comment_id', 'platform', 'body', 'posted_at'];
        $rows = [];
        foreach ($decoded as $index => $item) {
            if (! is_array($item)) {
                continue;
            }
            $missing = array_diff($required, array_keys($item));
            if (! empty($missing)) {
                $this->error("Fila JSON #{$index}: faltan columnas requeridas: ".implode(', ', $missing));

                return collect();
            }
            $item['_row'] = $index + 1;
            $rows[] = $item;
        }

        return collect($rows);
    }

    private function runDryRun(Collection $rows, ?SocialAccount $account): int
    {
        $this->info('=== VISTA PREVIA (Dry Run) ===');
        $this->newLine();

        $validRows = 0;
        $invalidRows = 0;
        $previewLimit = 5;
        $previewed = 0;

        foreach ($rows as $row) {
            $validation = $this->validateRow($row);

            if ($validation->fails()) {
                $invalidRows++;

                continue;
            }

            $validRows++;

            if ($previewed < $previewLimit) {
                $this->info("Fila #{$row['_row']}");
                $this->line("  external_comment_id: {$row['external_comment_id']}");
                $this->line("  platform: {$row['platform']}");
                $this->line('  post_id: '.($row['post_id'] ?? '—'));
                $this->line('  author_name: '.($row['author_name'] ?? '—'));
                $this->line('  body: '.mb_substr((string) ($row['body'] ?? ''), 0, 60));
                $this->line("  posted_at: {$row['posted_at']}");
                $this->newLine();
                $previewed++;
            }
        }

        $this->info('=== RESUMEN ===');
        $this->line("Total filas: {$rows->count()}");
        $this->line("Válidas: {$validRows}");
        $this->line("Inválidas: {$invalidRows}");

        if ($account !== null) {
            $this->line("Cuenta asociada: {$account->name} ({$account->platform})");
        }

        $this->newLine();
        $this->warn('No se guardó ningún cambio (modo dry-run).');

        return self::SUCCESS;
    }

    private function runImport(Collection $rows, ?SocialAccount $account): int
    {
        if ($account === null) {
            $this->error('Se requiere --account para importar comentarios.');

            return self::FAILURE;
        }

        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        foreach ($rows as $row) {
            $this->processRow($row, $account);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('=== RESUMEN DE IMPORTACIÓN ===');
        $this->line("Importados: {$this->imported}");
        $this->line("Omitidos (existentes): {$this->skipped}");
        $this->line("Fallidos: {$this->failed}");
        $this->line("Total: {$rows->count()}");

        if (! empty($this->failures)) {
            $this->newLine();
            $this->warn('=== ERRORES ===');
            foreach ($this->failures as $failure) {
                $this->error("Fila #{$failure['row']}: {$failure['reason']}");
            }
        }

        return $this->failed > 0 && $this->imported === 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processRow(array $row, SocialAccount $account): void
    {
        $validation = $this->validateRow($row);

        if ($validation->fails()) {
            $this->failed++;
            $this->failures[] = [
                'row' => $row['_row'],
                'reason' => implode(', ', $validation->errors()->all()),
            ];

            return;
        }

        $platform = strtolower((string) $row['platform']);
        $externalCommentId = (string) $row['external_comment_id'];

        $existing = SocialComment::query()
            ->where('platform', $platform)
            ->where('external_comment_id', $externalCommentId)
            ->first();

        if ($existing !== null && $this->option('skip-existing')) {
            $this->skipped++;

            return;
        }

        $data = [
            'social_account_id' => $account->id,
            'platform' => $platform,
            'external_comment_id' => $externalCommentId,
            'external_post_id' => (string) ($row['post_id'] ?? ''),
            'external_user_id' => (string) ($row['author_id'] ?? ''),
            'author_name' => $row['author_name'] ?? null,
            'body' => (string) $row['body'],
            'intent' => $row['intent'] ?? null,
            'urgency' => $row['urgency'] ?? null,
            'status' => $row['status'] ?? 'pending',
            'posted_at' => Carbon::parse((string) $row['posted_at']),
        ];

        if ($existing !== null) {
            $existing->update($data);
        } else {
            SocialComment::create($data);
        }

        $this->imported++;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function validateRow(array $row): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($row, [
            'external_comment_id' => ['required', 'string'],
            'platform' => ['required', 'string', 'in:facebook,instagram,whatsapp'],
            'body' => ['required', 'string'],
            'posted_at' => ['required', 'date'],
            'status' => ['nullable', 'string', 'in:pending,replied,escalated,spam'],
            'urgency' => ['nullable', 'string', 'in:low,medium,high,critical'],
        ]);
    }
}
