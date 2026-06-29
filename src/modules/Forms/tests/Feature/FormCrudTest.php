<?php

namespace Modules\Forms\Tests\Feature;

use Modules\Forms\Models\Form;
use Modules\Forms\Tests\TestCase;

class FormCrudTest extends TestCase
{
    public function test_admin_can_list_forms(): void
    {
        $user = $this->createUser(['Forms.forms.index']);
        Form::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('settings.forms.index'));

        $response->assertOk();
    }

    public function test_admin_can_create_form(): void
    {
        $user = $this->createUser(['Forms.forms.create']);

        $response = $this->actingAs($user)->post(route('settings.forms.store'), [
            'name' => 'Test Form',
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('forms', ['name' => 'Test Form']);
    }

    public function test_admin_can_update_form(): void
    {
        $user = $this->createUser(['Forms.forms.edit']);
        $form = $this->createForm();

        $response = $this->actingAs($user)->patch(route('settings.forms.update', $form), [
            'name' => 'Updated Form Name',
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'name' => 'Updated Form Name',
        ]);
    }

    public function test_admin_can_delete_form(): void
    {
        $user = $this->createUser(['Forms.forms.delete']);
        $form = $this->createForm();

        $response = $this->actingAs($user)->delete(route('settings.forms.destroy', $form));

        $response->assertRedirect(route('settings.forms.index'));

        $this->assertDatabaseMissing('forms', ['id' => $form->id]);
    }

    public function test_cannot_delete_form_with_submissions(): void
    {
        $user = $this->createUser(['Forms.forms.delete']);
        $form = $this->createForm();
        $this->createSubmission($form);

        $response = $this->actingAs($user)->delete(route('settings.forms.destroy', $form));

        $response->assertRedirect();

        $this->assertDatabaseHas('forms', ['id' => $form->id]);
    }

    public function test_admin_can_clone_form(): void
    {
        $user = $this->createUser(['Forms.forms.create']);
        $form = $this->createForm(['name' => 'Original Form']);

        $response = $this->actingAs($user)->postJson(route('settings.forms.clone', $form));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('forms', ['name' => 'Copia de Original Form']);
    }

    public function test_admin_can_export_form_json(): void
    {
        $user = $this->createUser(['Forms.forms.index']);
        $form = $this->createForm();

        $response = $this->actingAs($user)->get(route('settings.forms.export-json', $form));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/json',
            $response->headers->get('Content-Type', '')
        );
    }

    public function test_guest_cannot_access_admin_forms(): void
    {
        $response = $this->get(route('settings.forms.index'));

        $response->assertRedirect(route('auth.login'));
    }
}
