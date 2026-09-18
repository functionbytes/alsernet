<?php

namespace Modules\Forms\Tests\Feature;

use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Tests\TestCase;

class FormSubmissionTest extends TestCase
{
    public function test_can_submit_active_form(): void
    {
        $form = $this->createActiveForm(['access_control' => 'public']);
        FormField::factory()->for($form)->create([
            'type' => 'text',
            'key' => 'nombre',
            'label' => 'Nombre',
            'is_required' => false,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        $response = $this->postJson(route('forms.public.submit', $form->slug), [
            'nombre' => 'Juan Perez',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('form_submissions', ['form_id' => $form->id]);
    }

    public function test_submission_requires_required_fields(): void
    {
        $form = $this->createActiveForm(['access_control' => 'public']);
        FormField::factory()->for($form)->requiredField()->create([
            'type' => 'text',
            'key' => 'nombre',
            'label' => 'Nombre',
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        $response = $this->postJson(route('forms.public.submit', $form->slug), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    public function test_cannot_submit_inactive_form(): void
    {
        $form = $this->createForm(['is_active' => false]);

        $response = $this->postJson(route('forms.public.submit', $form->slug), []);

        $response->assertNotFound();
    }

    public function test_cannot_submit_expired_form(): void
    {
        $form = $this->createActiveForm([
            'access_control' => 'public',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->postJson(route('forms.public.submit', $form->slug), []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_honeypot_rejects_bots_silently(): void
    {
        $form = $this->createActiveForm([
            'access_control' => 'public',
            'honeypot_enabled' => true,
        ]);

        $response = $this->postJson(route('forms.public.submit', $form->slug), [
            '_hp' => 'bot-filled-this',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('form_submissions', ['form_id' => $form->id]);
    }

    public function test_password_protected_form_requires_password(): void
    {
        $form = Form::factory()
            ->active()
            ->passwordProtected()
            ->create(['access_control' => 'public']);

        $response = $this->postJson(route('forms.public.submit', $form->slug), []);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_submission_stores_values(): void
    {
        $form = $this->createActiveForm(['access_control' => 'public']);
        FormField::factory()->for($form)->create([
            'type' => 'text',
            'key' => 'mensaje',
            'label' => 'Mensaje',
            'is_required' => false,
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        $this->postJson(route('forms.public.submit', $form->slug), [
            'mensaje' => 'Hello world',
        ]);

        $submission = FormSubmission::where('form_id', $form->id)->first();

        $this->assertNotNull($submission);
        $this->assertDatabaseHas('form_submission_values', [
            'submission_id' => $submission->id,
            'field_key' => 'mensaje',
            'value' => 'Hello world',
        ]);
    }

    public function test_submission_tracks_utm_parameters(): void
    {
        $form = $this->createActiveForm(['access_control' => 'public']);

        $this->postJson(route('forms.public.submit', $form->slug), [
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'spring_sale',
        ]);

        $this->assertDatabaseHas('form_submissions', [
            'form_id' => $form->id,
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'spring_sale',
        ]);
    }
}
