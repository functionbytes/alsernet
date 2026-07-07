<?php

namespace Modules\Attention\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Mail\SlaBreachEscalationMail;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionSlaBreach;
use Modules\Attention\Models\AttentionSlaPolicy;
use Modules\Attention\Services\BusinessDaysService;

class CheckAttentionSlaBreachesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('sla');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting SLA breach check for attentions');

        // Get all active attentions that are not closed
        $attentions = Attention::whereIn('status', [
            AttentionStatus::RECEIVED,
            AttentionStatus::IN_PROCESS,
        ])
            ->with(['slaPolicy', 'type'])
            ->get();

        Log::info("Checking {$attentions->count()} attentions for SLA breaches");

        foreach ($attentions as $attention) {
            try {
                $this->checkAttentionSla($attention);
            } catch (\Exception $e) {
                Log::error("Error checking SLA for attention {$attention->id}: {$e->getMessage()}");
            }
        }

        Log::info('SLA breach check completed');
    }

    protected function checkAttentionSla(Attention $attention): void
    {
        $policy = $attention->slaPolicy ?? AttentionSlaPolicy::getDefault();

        if (! $policy || ! $policy->active) {
            return;
        }

        // Get type multiplier
        $typeCode = $attention->type?->code ?? 'PETICION';
        $multiplier = $policy->getMultiplierForType($typeCode);

        // Check response SLA
        $this->checkResponseSla($attention, $policy, $multiplier);

        // Check resolution SLA (only if status is IN_PROCESS)
        if ($attention->status === AttentionStatus::IN_PROCESS) {
            $this->checkResolutionSla($attention, $policy, $multiplier);
        }
    }

    protected function checkResponseSla(Attention $attention, AttentionSlaPolicy $policy, float $multiplier): void
    {
        // If already has a first action/note, response was given
        if ($attention->actions()->count() > 0 || $attention->notes()->count() > 0) {
            // Resolve any existing response breaches
            AttentionSlaBreach::where('attention_id', $attention->id)
                ->where('breach_type', 'response')
                ->where('resolved', false)
                ->each(fn ($breach) => $breach->resolve('Response was provided'));

            return;
        }

        $responseTimeMinutes = (int) ($policy->response_time * $multiplier);

        // IMPORTANTE: Los SLA se calculan en días HÁBILES según Ley 1437/2011 (CPACA)
        // Excluye sábados, domingos y festivos colombianos
        $elapsedMinutes = BusinessDaysService::diffInBusinessMinutes($attention->created_at, now());

        if ($elapsedMinutes > $responseTimeMinutes) {
            $minutesOver = $elapsedMinutes - $responseTimeMinutes;

            // Check if breach already exists
            $breach = AttentionSlaBreach::firstOrCreate(
                [
                    'attention_id' => $attention->id,
                    'sla_policy_id' => $policy->id,
                    'breach_type' => 'response',
                ],
                [
                    'minutes_over' => $minutesOver,
                    'escalated' => false,
                    'resolved' => false,
                ]
            );

            // Update minutes over if breach exists
            if (! $breach->wasRecentlyCreated) {
                $breach->update(['minutes_over' => $minutesOver]);
            }

            // Check if escalation is needed
            $escalationThreshold = $policy->getEscalationThreshold($responseTimeMinutes);
            if ($elapsedMinutes > $escalationThreshold && ! $breach->escalated) {
                $this->escalateBreach($breach, $attention, $policy, 'response');
            }
        }
    }

    protected function checkResolutionSla(Attention $attention, AttentionSlaPolicy $policy, float $multiplier): void
    {
        // If resolved_at is set, resolution was provided
        if ($attention->resolved_at) {
            // Resolve any existing resolution breaches
            AttentionSlaBreach::where('attention_id', $attention->id)
                ->where('breach_type', 'resolution')
                ->where('resolved', false)
                ->each(fn ($breach) => $breach->resolve('Resolution was provided'));

            return;
        }

        $resolutionTimeMinutes = (int) ($policy->resolution_time * $multiplier);

        // IMPORTANTE: Los SLA se calculan en días HÁBILES según Ley 1437/2011 (CPACA)
        // Excluye sábados, domingos y festivos colombianos
        $elapsedMinutes = BusinessDaysService::diffInBusinessMinutes($attention->created_at, now());

        if ($elapsedMinutes > $resolutionTimeMinutes) {
            $minutesOver = $elapsedMinutes - $resolutionTimeMinutes;

            // Check if breach already exists
            $breach = AttentionSlaBreach::firstOrCreate(
                [
                    'attention_id' => $attention->id,
                    'sla_policy_id' => $policy->id,
                    'breach_type' => 'resolution',
                ],
                [
                    'minutes_over' => $minutesOver,
                    'escalated' => false,
                    'resolved' => false,
                ]
            );

            // Update minutes over if breach exists
            if (! $breach->wasRecentlyCreated) {
                $breach->update(['minutes_over' => $minutesOver]);
            }

            // Check if escalation is needed
            $escalationThreshold = $policy->getEscalationThreshold($resolutionTimeMinutes);
            if ($elapsedMinutes > $escalationThreshold && ! $breach->escalated) {
                $this->escalateBreach($breach, $attention, $policy, 'resolution');
            }
        }
    }

    protected function checkClosureSla(Attention $attention, AttentionSlaPolicy $policy, float $multiplier): void
    {
        // If closed_at is set, closure was provided
        if ($attention->closed_at) {
            // Resolve any existing closure breaches
            AttentionSlaBreach::where('attention_id', $attention->id)
                ->where('breach_type', 'closure')
                ->where('resolved', false)
                ->each(fn ($breach) => $breach->resolve('Closure was completed'));

            return;
        }

        // Only check closure SLA if attention is resolved
        if ($attention->status !== AttentionStatus::RESOLVED) {
            return;
        }

        $closureTimeMinutes = (int) ($policy->closure_time * $multiplier);

        // IMPORTANTE: Los SLA se calculan en días HÁBILES según Ley 1437/2011 (CPACA)
        // Excluye sábados, domingos y festivos colombianos
        $elapsedMinutes = BusinessDaysService::diffInBusinessMinutes($attention->created_at, now());

        if ($elapsedMinutes > $closureTimeMinutes) {
            $minutesOver = $elapsedMinutes - $closureTimeMinutes;

            // Check if breach already exists
            $breach = AttentionSlaBreach::firstOrCreate(
                [
                    'attention_id' => $attention->id,
                    'sla_policy_id' => $policy->id,
                    'breach_type' => 'closure',
                ],
                [
                    'minutes_over' => $minutesOver,
                    'escalated' => false,
                    'resolved' => false,
                ]
            );

            // Update minutes over if breach exists
            if (! $breach->wasRecentlyCreated) {
                $breach->update(['minutes_over' => $minutesOver]);
            }

            // Check if escalation is needed
            $escalationThreshold = $policy->getEscalationThreshold($closureTimeMinutes);
            if ($elapsedMinutes > $escalationThreshold && ! $breach->escalated) {
                $this->escalateBreach($breach, $attention, $policy, 'closure');
            }
        }
    }

    protected function escalateBreach(
        AttentionSlaBreach $breach,
        Attention $attention,
        AttentionSlaPolicy $policy,
        string $breachType
    ): void {
        $breach->escalate();

        Log::warning("SLA breach escalated for attention {$attention->radicado}", [
            'breach_id' => $breach->id,
            'breach_type' => $breachType,
            'minutes_over' => $breach->minutes_over,
        ]);

        // Send notification emails
        if ($policy->enable_escalation && ! empty($policy->escalation_recipients)) {
            $this->sendEscalationNotifications($breach, $attention, $policy, $breachType);
        }
    }

    protected function sendEscalationNotifications(
        AttentionSlaBreach $breach,
        Attention $attention,
        AttentionSlaPolicy $policy,
        string $breachType
    ): void {
        $recipients = $policy->escalation_recipients ?? [];

        foreach ($recipients as $recipient) {
            if (! isset($recipient['email'])) {
                continue;
            }

            try {
                Mail::to($recipient['email'])->send(
                    new SlaBreachEscalationMail($attention, $breach, $recipient['role'] ?? 'supervisor')
                );

                Log::info('SLA breach escalation email sent', [
                    'radicado' => $attention->radicado,
                    'recipient' => $recipient['email'],
                    'breach_type' => $breachType,
                    'level' => $breach->escalated ? 'ESCALADO' : 'PENDIENTE',
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send SLA escalation email: {$e->getMessage()}");
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CheckAttentionSlaBreachesJob failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
