<?php

namespace Modules\Social\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\Social\Models\BulkImport;
use Modules\Social\Models\Post;

class ProcessBulkImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public $backoff = [60, 300, 900];

    public function __construct(public BulkImport $bulkImport) {}

    public function handle(): void
    {
        $this->bulkImport->markAsProcessing();

        try {
            $content = Storage::disk('local')->get($this->bulkImport->file_path);
            $rows = array_map('str_getcsv', explode("\n", trim($content)));
            $header = array_shift($rows);

            $this->bulkImport->update(['total_rows' => count($rows)]);

            foreach ($rows as $index => $row) {
                try {
                    $data = array_combine($header, $row);

                    Post::create([
                        'account_id' => $this->bulkImport->account_id,
                        'created_by' => $this->bulkImport->user_id,
                        'social_account_id' => $data['social_account_id'] ?? null,
                        'campaign_id' => $data['campaign_id'] ?? null,
                        'content' => $data['content'],
                        'type' => $data['type'] ?? 'text',
                        'scheduled_at' => $data['scheduled_at'] ?? null,
                        'status' => $data['status'] ?? 'draft',
                    ]);

                    $this->bulkImport->increment('created_posts');
                } catch (\Exception $e) {
                    $this->bulkImport->increment('failed_rows');

                    $errors = $this->bulkImport->errors ?? [];
                    $errors[] = "Row {$index}: {$e->getMessage()}";
                    $this->bulkImport->update(['errors' => $errors]);
                }

                $this->bulkImport->increment('processed_rows');
            }

            $this->bulkImport->markAsCompleted();
        } catch (\Exception $e) {
            $this->bulkImport->markAsFailed($e->getMessage());
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Cubre el caso que el catch interno no puede: si el worker muere a
        // mitad de proceso (OOM, kill del supervisor), Laravel marca el job
        // fallido sin pasar por el catch de handle() — sin esto el import
        // quedaba atascado en 'processing' para siempre.
        if ($this->bulkImport->status === 'processing') {
            $this->bulkImport->markAsFailed($exception->getMessage());
        }
    }
}
