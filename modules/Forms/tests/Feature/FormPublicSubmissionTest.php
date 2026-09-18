<?php

namespace Modules\Forms\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Forms\Jobs\SendFormWebhookJob;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;
use Modules\Forms\Models\FormSubmission;
use Modules\Forms\Tests\TestCase;

class FormPublicSubmissionTest extends TestCase
{
    // ========== SUCCESSFUL SUBMISSION ==========

    public function test_public_form_submission_succeeds(): void
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
            'nombre' => 'Juan Pérez',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    // ========== DB RECORD ==========

    public function test_successful_submission_creates_database_record(): void
    {
        $form = $this->createActiveForm(['access_control' => 'public']);

        $this->postJson(route('forms.public.submit', $form->slug), []);

        $this->assertDatabaseHas('form_submissions', [
            'form_id' => $form->id,
            'status' => 'new',
        ]);
    }

    // ========== REQUIRED FIELDS VALIDATION ==========

    public function test_submission_fails_when_required_field_is_missing(): void
    {
        $form = $this->createActiveForm(['access_control' => 'public']);
        FormField::factory()->for($form)->requiredField()->create([
            'type' => 'text',
            'key' => 'email',
            'label' => 'Email',
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        $response = $this->postJson(route('forms.public.submit', $form->slug), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_submission_passes_when_required_field_is_provided(): void
    {
        $form = $this->createActiveForm(['access_control' => 'public']);
        FormField::factory()->for($form)->requiredField()->create([
            'type' => 'text',
            'key' => 'email',
            'label' => 'Email',
            'is_visible' => true,
            'sort_order' => 1,
        ]);

        $response = $this->postJson(route('forms.public.submit', $form->slug), [
            'email' => 'test@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    // ========== HONEYPOT ==========

    public function test_honeypot_field_filled_returns_fake_success_without_storing(): void
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

    public function test_empty_honeypot_field_does_not_block_submission(): void
    {
        $form = $this->createActiveForm([
            'access_control' => 'public',
            'honeypot_enabled' => true,
        ]);

        $response = $this->postJson(route('forms.public.submit', $form->slug), [
            '_hp' => '',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('form_submissions', ['form_id' => $form->id]);
    }

    // ========== RATE LIMITING ==========

    public function test_submission_rate_limit_blocks_after_five_requests(): void
    {
        $form = $this->createActiveForm(['access_control' => 'public']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('forms.public.submit', $form->slug), []);
        }

        $response = $this->postJson(route('forms.public.submit', $form->slug), []);

        $response->assertStatus(429);
    }

    // ========== WEBHOOK DISPATCHED ==========

    public function test_webhook_job_is_dispatched_when_webhook_url_is_set(): void
    {
        Queue::fake();

        $form = $this->createActiveForm([
            'access_control' => 'public',
            'webhook_url' => 'https://example.com/webhook',
        ]);

        $this->postJson(route('forms.public.submit', $form->slug), []);

        Queue::assertPushed(SendFormWebhookJob::class);
    }

    public function test_webhook_job_is_not_dispatched_when_webhook_url_is_absent(): void
    {
        Queue::fake();

        $form = $this->createActiveForm([
            'access_control' => 'public',
            'webhook_url' => null,
        ]);

        $this->postJson(route('forms.public.submit', $form->slug), []);

        Queue::assertNotPushed(SendFormWebhookJob::class);
    }

    // ========== EDGE CASES ==========

    public function test_inactive_form_returns_404(): void
    {
        $form = Form::factory()->inactive()->create(['access_control' => 'public']);

        $response = $this->postJson(route('forms.public.submit', $form->slug), []);

        $response->assertNotFound();
    }

    public function test_expired_form_returns_error(): void
    {
        $form = $this->createActiveForm([
            'access_control' => 'public',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->postJson(route('forms.public.submit', $form->slug), []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_form_at_max_submissions_returns_error(): void
    {
        $form = $this->createActiveForm([
            'access_control' => 'public',
            'max_submissions' => 1,
        ]);

        FormSubmission::factory()->for($form)->create();

        $response = $this->postJson(route('forms.public.submit', $form->slug), []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
