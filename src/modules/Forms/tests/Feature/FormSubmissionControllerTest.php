<?php

namespace Modules\Forms\Tests\Feature;

use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Tests\TestCase;

class FormSubmissionControllerTest extends TestCase
{
    public function test_admin_can_list_submissions(): void
    {
        $user = $this->createUser(['Forms.submissions.index']);
        $form = $this->createForm();
        FormSubmission::factory()->for($form)->count(3)->create();

        $response = $this->actingAs($user)->get(route('settings.forms.submissions.index', $form));

        $response->assertOk();
    }

    public function test_admin_can_view_submission(): void
    {
        $user = $this->createUser(['Forms.submissions.index']);
        $form = $this->createForm();
        $submission = $this->createSubmission($form);

        $response = $this->actingAs($user)->get(
            route('settings.forms.submissions.show', [$form, $submission])
        );

        $response->assertOk();
    }

    public function test_viewing_submission_marks_it_as_read(): void
    {
        $user = $this->createUser(['Forms.submissions.index']);
        $form = $this->createForm();
        $submission = $this->createSubmission($form, ['is_read' => false]);

        $this->actingAs($user)->get(
            route('settings.forms.submissions.show', [$form, $submission])
        );

        $this->assertDatabaseHas('form_submissions', [
            'id' => $submission->id,
            'is_read' => true,
        ]);
    }

    public function test_admin_can_update_submission_status(): void
    {
        $user = $this->createUser(['Forms.submissions.index']);
        $form = $this->createForm();
        $submission = $this->createSubmission($form, ['status' => 'new']);

        $response = $this->actingAs($user)->patchJson(
            route('settings.forms.submissions.status', [$form, $submission]),
            ['status' => 'resolved']
        );

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('form_submissions', [
            'id' => $submission->id,
            'status' => 'resolved',
        ]);
    }

    public function test_admin_can_toggle_star(): void
    {
        $user = $this->createUser(['Forms.submissions.index']);
        $form = $this->createForm();
        $submission = $this->createSubmission($form, ['is_starred' => false]);

        $response = $this->actingAs($user)->patchJson(
            route('settings.forms.submissions.toggle-star', [$form, $submission])
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_starred', true);

        $this->assertDatabaseHas('form_submissions', [
            'id' => $submission->id,
            'is_starred' => true,
        ]);
    }

    public function test_admin_can_delete_submission(): void
    {
        $user = $this->createUser(['Forms.submissions.delete']);
        $form = $this->createForm();
        $submission = $this->createSubmission($form);

        $response = $this->actingAs($user)->deleteJson(
            route('settings.forms.submissions.destroy', [$form, $submission])
        );

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('form_submissions', ['id' => $submission->id]);
    }

    public function test_admin_can_bulk_delete_submissions(): void
    {
        $user = $this->createUser(['Forms.submissions.delete']);
        $form = $this->createForm();
        $submissions = FormSubmission::factory()->for($form)->count(3)->create();
        $ids = $submissions->pluck('id')->all();

        $response = $this->actingAs($user)->postJson(
            route('settings.forms.submissions.bulk-delete', $form),
            ['ids' => $ids]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('deleted', 3);

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('form_submissions', ['id' => $id]);
        }
    }

    public function test_admin_can_add_note(): void
    {
        $user = $this->createUser(['Forms.submissions.index']);
        $form = $this->createForm();
        $submission = $this->createSubmission($form);

        $response = $this->actingAs($user)->postJson(
            route('settings.forms.submissions.notes.add', [$form, $submission]),
            ['note' => 'This is a test note.']
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['note' => ['id', 'note', 'created_at', 'user']]);

        $this->assertDatabaseHas('form_submission_notes', [
            'submission_id' => $submission->id,
            'note' => 'This is a test note.',
        ]);
    }

    public function test_admin_can_export_submissions(): void
    {
        $user = $this->createUser(['Forms.submissions.export']);
        $form = $this->createForm();
        FormSubmission::factory()->for($form)->count(2)->create();

        $response = $this->actingAs($user)->post(
            route('settings.forms.submissions.export', $form)
        );

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            $response->headers->get('Content-Type', '')
        );
    }

    public function test_guest_cannot_access_submissions(): void
    {
        $form = $this->createForm();

        $response = $this->get(route('settings.forms.submissions.index', $form));

        $response->assertRedirect(route('auth.login'));
    }
}
