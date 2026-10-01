<?php

namespace Modules\HelpdeskAiPrompts\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\HelpdeskAiPrompts\Services\Quality\QualityAlertEvaluator;

/**
 * Aviso de que un caso del asistente IA supera un umbral de calidad en las
 * últimas horas. Canal database (campana del panel); email solo si
 * helpdeskaiprompts_quality.alerts.mail está activo.
 */
class AiPromptQualityAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{case_key: string, type: string, value: float, threshold: float, sample: int}  $alert
     */
    public function __construct(
        public readonly array $alert,
        public readonly string $caseName,
    ) {
        $this->onQueue('notifications');
    }

    public function via(mixed $notifiable): array
    {
        return config('helpdeskaiprompts_quality.alerts.mail') ? ['database', 'mail'] : ['database'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->line($this->message())
            ->action(__('helpdeskaiprompts::quality.notification_action'), route('helpdesk-ai-prompts.quality.index'));
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'ai_prompt_quality_alert',
            'title' => $this->title(),
            'message' => $this->message(),
            'case_key' => $this->alert['case_key'],
            'alert_type' => $this->alert['type'],
            'value' => $this->alert['value'],
            'threshold' => $this->alert['threshold'],
            'url' => route('helpdesk-ai-prompts.quality.index'),
        ];
    }

    private function title(): string
    {
        return __('helpdeskaiprompts::quality.notification_title', ['case' => $this->caseName]);
    }

    private function message(): string
    {
        $key = match ($this->alert['type']) {
            QualityAlertEvaluator::TYPE_ESCALATION => 'notification_escalation',
            QualityAlertEvaluator::TYPE_DISLIKES => 'notification_dislikes',
            default => 'notification_cost',
        };

        return __("helpdeskaiprompts::quality.{$key}", [
            'value' => $this->alert['value'],
            'threshold' => $this->alert['threshold'],
            'sample' => $this->alert['sample'],
            'hours' => config('helpdeskaiprompts_quality.alerts.window_hours'),
        ]);
    }
}
