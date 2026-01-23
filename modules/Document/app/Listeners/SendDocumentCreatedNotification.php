<?php

namespace Modules\Document\Listeners;

use Illuminate\Notifications\Notification;
use Modules\Document\Entities\DocumentValidatorGroup;
use Modules\Document\Events\DocumentCreated;

class SendDocumentCreatedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(DocumentCreated $event): void
    {
        $document = $event->document;

        // Load the document type relationship
        $document->load('documentType');

        if (! $document->documentType) {
            return;
        }

        // Get the first validation stage for this document type
        $firstStage = $document->documentType->validationStages()
            ->active()
            ->ordered()
            ->first();

        if (! $firstStage) {
            return;
        }

        // Get the validator group for this stage
        $validatorGroup = DocumentValidatorGroup::findByKey($firstStage->key);

        if (! $validatorGroup) {
            \Log::warning("Validator group not found for stage: {$firstStage->key}");

            return;
        }

        // Get all users in this validator group
        $users = $validatorGroup->users()->get();

        if ($users->isEmpty()) {
            \Log::warning("No users found in validator group: {$firstStage->key}");

            return;
        }

        // Send notification to all users in the validator group
        foreach ($users as $user) {
            $notification = new class extends Notification implements \Illuminate\Contracts\Broadcasting\ShouldBroadcast, \Illuminate\Contracts\Queue\ShouldQueue
            {
                use \Illuminate\Bus\Queueable;

                public ?int $recipientUserId = null;

                public function __construct(
                    public $document,
                    public $groupKey
                ) {}

                public function via($notifiable)
                {
                    $this->recipientUserId = $notifiable->id;

                    return ['database', 'broadcast'];
                }

                public function toDatabase($notifiable)
                {
                    return [
                        'title' => '📄 Nuevo documento para validar',
                        'message' => "Se ha creado un nuevo documento para la orden #{$this->document->order_id}. ".
                                     "Cliente: {$this->document->customer_firstname} {$this->document->customer_lastname}. ".
                                     "Requiere validación de {$this->groupKey}.",
                        'icon' => 'fa-duotone fas fa-file-check',
                        'color' => 'warning',
                        'action_url' => url("/documents/show/{$this->document->uid}"),
                        'action_text' => 'Validar documento',
                        'priority' => 'high',
                        'document_id' => $this->document->id,
                        'document_uid' => $this->document->uid,
                        'validator_group' => $this->groupKey,
                    ];
                }

                public function toBroadcast($notifiable)
                {
                    return new \Illuminate\Notifications\Messages\BroadcastMessage([
                        'title' => '📄 Nuevo documento para validar',
                        'message' => "Documento #{$this->document->order_id} requiere validación de {$this->groupKey}",
                        'icon' => 'fa-duotone fas fa-file-check',
                        'color' => 'warning',
                        'action_url' => url("/documents/show/{$this->document->uid}"),
                        'action_text' => 'Validar documento',
                        'priority' => 'high',
                        'document_id' => $this->document->id,
                        'document_uid' => $this->document->uid,
                        'validator_group' => $this->groupKey,
                    ]);
                }

                public function broadcastOn(): array
                {
                    return [
                        new \Illuminate\Broadcasting\Channel('documents.created'),
                    ];
                }

                public function broadcastType(): string
                {
                    return 'document.created.notification';
                }
            };

            $user->notify($notification);
        }

        \Log::info('Document creation notifications sent to validator group', [
            'document_id' => $document->id,
            'document_uid' => $document->uid,
            'validator_group' => $firstStage->key,
            'users_notified' => $users->count(),
        ]);
    }
}
