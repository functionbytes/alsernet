<?php

namespace Modules\Remarketing\Steps;

use Illuminate\Support\Str;
use Modules\Remarketing\Contracts\StepHandlerInterface;
use Modules\Remarketing\Models\AutomationRun;
use Modules\Remarketing\Models\Message;
use Modules\Remarketing\Models\Template;
use Modules\Remarketing\Services\ChannelRegistry;

class SendEmailStepHandler implements StepHandlerInterface
{
    public function __construct(
        private readonly ChannelRegistry $channels
    ) {}

    public function execute(AutomationRun $run, array $config): void
    {
        $templateId = $config['template_id'] ?? null;
        $template = $templateId ? Template::query()->find($templateId) : null;
        $subject = $config['subject'] ?? $template?->subject ?? 'Sin asunto';

        $message = Message::query()->create([
            'store_id' => $run->automation->store_id,
            'customer_id' => $run->customer_id,
            'automation_run_id' => $run->id,
            'email' => $run->customer?->email ?? '',
            'subject' => $subject,
            'status' => 'queued',
            'open_token' => Str::random(64),
            'click_token' => Str::random(64),
        ]);

        $this->channels->send($message, 'email');

        $run->update(['next_step_at' => now()]);
    }
}
