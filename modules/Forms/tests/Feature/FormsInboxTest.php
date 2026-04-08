<?php

namespace Modules\Forms\Tests\Feature;

use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Tests\TestCase;

class FormsInboxTest extends TestCase
{
    public function test_inbox_requires_authentication(): void
    {
        $this->get(route('forms.inbox.index'))
            ->assertRedirect();
    }

    public function test_inbox_index_loads_for_authenticated_user(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('forms.inbox.index'))
            ->assertOk();
    }

    public function test_inbox_index_shows_submissions(): void
    {
        $user = $this->createUser();
        $form = $this->createForm();
        FormSubmission::factory()->count(3)->for($form)->create();

        $this->actingAs($user)
            ->get(route('forms.inbox.index'))
            ->assertOk();
    }

    public function test_inbox_show_loads_submission(): void
    {
        $user = $this->createUser();
        $submission = $this->createSubmission($this->createForm());

        $this->actingAs($user)
            ->get(route('forms.inbox.show', $submission))
            ->assertOk();
    }

    public function test_inbox_show_marks_submission_as_read(): void
    {
        $user = $this->createUser();
        $submission = $this->createSubmission($this->createForm(), ['is_read' => false]);

        $this->actingAs($user)
            ->get(route('forms.inbox.show', $submission));

        $this->assertDatabaseHas('form_submissions', [
            'id' => $submission->id,
            'is_read' => true,
        ]);
    }

    public function test_inbox_dashboard_loads(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(route('forms.inbox.dashboard'))
            ->assertOk();
    }

    public function test_inbox_filters_by_form(): void
    {
        $user = $this->createUser();
        $formA = $this->createForm();
        $formB = $this->createForm();
        $this->createSubmission($formA);
        $this->createSubmission($formB);

        $this->actingAs($user)
            ->get(route('forms.inbox.index', ['form_id' => $formA->id]))
            ->assertOk();
    }

    public function test_inbox_filters_by_status(): void
    {
        $user = $this->createUser();
        $form = $this->createForm();
        $this->createSubmission($form, ['status' => 'new']);
        $this->createSubmission($form, ['status' => 'resolved']);

        $this->actingAs($user)
            ->get(route('forms.inbox.index', ['status' => 'new']))
            ->assertOk();
    }
}
