<?php

namespace Modules\Forms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Modules\Forms\Models\FormAbandonTracking;

class SendAbandonReminderCommand extends Command
{
    protected $signature = 'forms:send-abandon-reminders {--hours=24 : Horas después del abandono}';

    protected $description = 'Envía emails de recordatorio a usuarios que abandonaron formularios';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $abandoned = FormAbandonTracking::query()
            ->with('form')
            ->needsReminder($hours)
            ->whereNotNull('email')
            ->get();

        foreach ($abandoned as $track) {
            try {
                Mail::send('forms::emails.abandon-reminder', [
                    'form' => $track->form,
                    'restoreUrl' => route('forms.public.restore', ['slug' => $track->form->slug]).'?token='.$track->session_token,
                ], function ($message) use ($track) {
                    $message->to($track->email)
                        ->subject('¿Olvidaste algo? Tu formulario te espera');
                });

                $track->update(['reminder_sent_at' => now()]);
                $this->info("Reminder enviado a: {$track->email}");
            } catch (\Throwable $e) {
                $this->error("Error con {$track->email}: {$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }
}
