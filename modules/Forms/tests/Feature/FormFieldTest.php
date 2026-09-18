<?php

namespace Modules\Forms\Tests\Feature;

use Modules\Forms\Models\FormField;
use Modules\Forms\Models\FormSubmissionValue;
use Modules\Forms\Tests\TestCase;

class FormFieldTest extends TestCase
{
    public function test_admin_can_get_fields(): void
    {
        $user = $this->createUser(['Forms.forms.edit']);
        $form = $this->createForm();
        FormField::factory()->for($form)->count(3)->create();

        $response = $this->actingAs($user)->getJson(route('settings.forms.fields.index', $form));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'fields');
    }

    public function test_admin_can_create_field(): void
    {
        $user = $this->createUser(['Forms.forms.edit']);
        $form = $this->createForm();

        $response = $this->actingAs($user)->postJson(route('settings.forms.fields.store', $form), [
            'label' => 'Full Name',
            'type' => 'text',
            'is_required' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('form_fields', [
            'form_id' => $form->id,
            'label' => 'Full Name',
            'type' => 'text',
        ]);
    }

    public function test_admin_can_update_field(): void
    {
        $user = $this->createUser(['Forms.forms.edit']);
        $form = $this->createForm();
        $field = $this->createField($form);

        $response = $this->actingAs($user)->patchJson(
            route('settings.forms.fields.update', [$form, $field]),
            ['label' => 'Updated Label', 'is_required' => true]
        );

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('form_fields', [
            'id' => $field->id,
            'label' => 'Updated Label',
            'is_required' => true,
        ]);
    }

    public function test_admin_can_delete_field(): void
    {
        $user = $this->createUser(['Forms.forms.edit']);
        $form = $this->createForm();
        $field = $this->createField($form);

        $response = $this->actingAs($user)->deleteJson(
            route('settings.forms.fields.destroy', [$form, $field])
        );

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('form_fields', ['id' => $field->id]);
    }

    public function test_admin_can_reorder_fields(): void
    {
        $user = $this->createUser(['Forms.forms.edit']);
        $form = $this->createForm();
        $fieldA = $this->createField($form, ['sort_order' => 1]);
        $fieldB = $this->createField($form, ['sort_order' => 2]);

        $response = $this->actingAs($user)->postJson(route('settings.forms.fields.reorder', $form), [
            'items' => [
                ['id' => $fieldA->id, 'sort_order' => 2],
                ['id' => $fieldB->id, 'sort_order' => 1],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('form_fields', ['id' => $fieldA->id, 'sort_order' => 2]);
        $this->assertDatabaseHas('form_fields', ['id' => $fieldB->id, 'sort_order' => 1]);
    }

    public function test_layout_field_is_not_required(): void
    {
        $user = $this->createUser(['Forms.forms.edit']);
        $form = $this->createForm();
        $field = $this->createField($form, ['type' => 'section_header']);

        $response = $this->actingAs($user)->patchJson(
            route('settings.forms.fields.update', [$form, $field]),
            ['label' => 'Section Title', 'is_required' => true]
        );

        $response->assertOk();

        $this->assertDatabaseHas('form_fields', [
            'id' => $field->id,
            'is_required' => false,
        ]);
    }

    public function test_cannot_delete_field_with_submission_values(): void
    {
        $user = $this->createUser(['Forms.forms.edit']);
        $form = $this->createForm();
        $field = $this->createField($form);
        $submission = $this->createSubmission($form);

        FormSubmissionValue::factory()->create([
            'submission_id' => $submission->id,
            'form_field_id' => $field->id,
            'field_key' => $field->key,
        ]);

        $response = $this->actingAs($user)->deleteJson(
            route('settings.forms.fields.destroy', [$form, $field])
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('form_fields', ['id' => $field->id]);
    }
}
