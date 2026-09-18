<?php

namespace Modules\Forms\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Modules\Forms\Events\FormSubmitted;
use Modules\Forms\Models\Form;
use Modules\Forms\Services\FormSubmissionService;
use Modules\Forms\Tests\TestCase;

class FormSubmittedEventTest extends TestCase
{
    public function test_form_submitted_event_dispatched_on_successful_submission(): void
    {
        Event::fake([FormSubmitted::class]);

        $form = $this->createActiveForm();
        $this->createField($form, ['label' => 'Nombre', 'key' => 'nombre', 'type' => 'text', 'is_visible' => true]);

        $request = request();
        $request->merge(['nombre' => 'Juan']);

        app(FormSubmissionService::class)->process($form, $request);

        Event::assertDispatched(FormSubmitted::class, function (FormSubmitted $event) use ($form): bool {
            return $event->form->id === $form->id && $event->submission->form_id === $form->id;
        });
    }

    public function test_form_submitted_carries_form_and_submission(): void
    {
        $form = Form::factory()->active()->create();
        $this->createField($form, ['label' => 'Email', 'key' => 'email', 'type' => 'email', 'is_visible' => true]);

        $request = request();
        $request->merge(['email' => 'test@example.com']);

        $submission = app(FormSubmissionService::class)->process($form, $request);

        $event = new FormSubmitted($form, $submission);

        $this->assertSame($form->id, $event->form->id);
        $this->assertSame($submission->id, $event->submission->id);
    }
}
