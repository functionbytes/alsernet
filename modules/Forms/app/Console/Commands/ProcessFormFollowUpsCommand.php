<?php

namespace Modules\Forms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Forms\Models\FormFollowUp;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Services\FormEmailService;

class ProcessFormFollowUpsCommand extends Command
{
    protected $signature = 'forms:process-follow-ups';

    protected $description = 'Procesa y envía los emails de seguimiento de formularios programados';

    public function __construct(private readonly FormEmailService $emailService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = 0;

        FormFollowUp::query()
            ->active()
            ->with('form')
            ->each(function (FormFollowUp $followUp) use (&$count) {
                $cutoff = now()->subDays($followUp->send_after_days);

                $submissions = $followUp->form->submissions()
                    ->where('created_at', '<=', $cutoff)
                    ->get();

                foreach ($submissions as $submission) {
                    if (! $this->meetCondition($followUp, $submission)) {
                        continue;
                    }

                    $this->sendFollowUp($followUp, $submission);
                    $count++;
                }
            });

        $this->info("Procesados: {$count} follow-ups");

        return Command::SUCCESS;
    }

    private function sendFollowUp(FormFollowUp $followUp, FormSubmission $submission): void
    {
        if (! $followUp->email_template_id) {
            return;
        }

        try {
            $form = $followUp->form;

            if (in_array($followUp->recipient_type, ['admin', 'both'], true)) {
                $this->emailService->notifyAdmin($form, $submission);
            }

            if (in_array($followUp->recipient_type, ['submitter', 'both'], true)) {
                $clientEmail = $submission->getEmailValue();
                if ($clientEmail) {
                    $this->emailService->sendConfirmation($form, $submission, $clientEmail);
                }
            }
        } catch (\Throwable $e) {
            Log::error("Forms: Error enviando follow-up #{$followUp->id}: {$e->getMessage()}");
        }
    }

    private function meetCondition(FormFollowUp $followUp, FormSubmission $submission): bool
    {
        if (! $followUp->condition_field_key) {
            return true;
        }

        $value = $submission->getValueFor($followUp->condition_field_key);

        return $value === $followUp->condition_value;
    }
}
