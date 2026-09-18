<?php

namespace Modules\Forms\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Forms\Exports\FormSubmissionsExport;
use Modules\Forms\Models\Form;
use Modules\Forms\Notifications\FormExportReadyNotification;

class ExportFormSubmissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 60;

    public function __construct(
        public readonly User $user,
        public readonly Form $form,
        public readonly array $filters = []
    ) {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $date = now()->format('Y-m-d');
        $filename = "submissions-{$this->form->slug}-{$date}.xlsx";
        $filepath = "exports/forms/{$filename}";

        Storage::disk('local')->makeDirectory('exports/forms');

        Excel::store(
            new FormSubmissionsExport($this->form, $this->filters),
            $filepath,
            'local'
        );

        Log::info('Form submissions export completed', [
            'user_id' => $this->user->id,
            'form_id' => $this->form->id,
            'filepath' => $filepath,
        ]);

        $this->user->notify(new FormExportReadyNotification($filepath, $this->form->name));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ExportFormSubmissionsJob failed permanently', [
            'user_id' => $this->user->id,
            'form_id' => $this->form->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
