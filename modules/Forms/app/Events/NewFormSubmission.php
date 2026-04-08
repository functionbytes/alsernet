<?php

namespace Modules\Forms\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;

class NewFormSubmission implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Form $form,
        public readonly FormSubmission $submission
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('forms'),
            new Channel('forms.'.$this->form->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new-submission';
    }

    public function broadcastWith(): array
    {
        return [
            'form_id' => $this->form->id,
            'form_name' => $this->form->name,
            'submission_id' => $this->submission->id,
            'created_at' => $this->submission->created_at->toIso8601String(),
        ];
    }
}
