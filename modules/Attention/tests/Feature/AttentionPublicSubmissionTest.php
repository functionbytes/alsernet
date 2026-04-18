<?php

namespace Modules\Attention\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Mail\AttentionConfirmationMail;
use Modules\Attention\Models\Attention;
use Modules\Attention\Tests\TestCase;

class AttentionPublicSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable captcha so tests can submit the public form freely
        config(['attention.captcha.enabled' => false]);
    }

    // ========== SUCCESSFUL SUBMISSION ==========

    public function test_valid_peticiones_submission_redirects_to_success_page(): void
    {
        $data = $this->validAttentionData();

        $response = $this->post(route('peticiones.submit'), $data);

        $response->assertRedirect();

        $attention = Attention::where('subject', $data['subject'])->first();
        $this->assertNotNull($attention);
        $this->assertStringContainsString(
            route('peticiones.success', $attention->radicado),
            $response->headers->get('Location')
        );
    }

    public function test_submission_creates_attention_record_with_received_status(): void
    {
        $data = $this->validAttentionData();

        $this->post(route('peticiones.submit'), $data);

        $this->assertDatabaseHas('attentions', [
            'subject' => $data['subject'],
            'status' => AttentionStatus::RECEIVED->value,
        ]);
    }

    public function test_submission_generates_radicado_with_peticiones_prefix(): void
    {
        $data = $this->validAttentionData();

        $this->post(route('peticiones.submit'), $data);

        $attention = Attention::where('subject', $data['subject'])->first();
        $this->assertNotNull($attention->radicado);
        $this->assertStringStartsWith('peticiones-', $attention->radicado);
    }

    // ========== RATE LIMITING ==========

    public function test_rate_limit_blocks_after_ten_submissions(): void
    {
        $data = $this->validAttentionData();

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('peticiones.submit'), array_merge($data, [
                'customer_email' => "ratelimit{$i}@example.com",
            ]));
        }

        $response = $this->post(route('peticiones.submit'), array_merge($data, [
            'customer_email' => 'ratelimit11@example.com',
        ]));

        $response->assertStatus(429);
    }

    // ========== REQUIRED FIELDS VALIDATION ==========

    public function test_missing_type_id_fails_validation(): void
    {
        $data = $this->validAttentionData();
        unset($data['type_id']);

        $response = $this->post(route('peticiones.submit'), $data);

        $response->assertSessionHasErrors(['type_id']);
    }

    public function test_missing_category_id_fails_validation(): void
    {
        $data = $this->validAttentionData();
        unset($data['category_id']);

        $response = $this->post(route('peticiones.submit'), $data);

        $response->assertSessionHasErrors(['category_id']);
    }

    public function test_missing_subject_fails_validation(): void
    {
        $data = $this->validAttentionData();
        unset($data['subject']);

        $response = $this->post(route('peticiones.submit'), $data);

        $response->assertSessionHasErrors(['subject']);
    }

    public function test_missing_description_fails_validation(): void
    {
        $data = $this->validAttentionData();
        unset($data['description']);

        $response = $this->post(route('peticiones.submit'), $data);

        $response->assertSessionHasErrors(['description']);
    }

    public function test_non_anonymous_submission_requires_customer_firstname(): void
    {
        $data = $this->validAttentionData(['is_anonymous' => false]);
        unset($data['customer_firstname']);

        $response = $this->post(route('peticiones.submit'), $data);

        $response->assertSessionHasErrors(['customer_firstname']);
    }

    public function test_non_anonymous_submission_requires_customer_email(): void
    {
        $data = $this->validAttentionData(['is_anonymous' => false]);
        unset($data['customer_email']);

        $response = $this->post(route('peticiones.submit'), $data);

        $response->assertSessionHasErrors(['customer_email']);
    }

    public function test_invalid_email_format_fails_validation(): void
    {
        $data = $this->validAttentionData(['customer_email' => 'not-a-valid-email']);

        $response = $this->post(route('peticiones.submit'), $data);

        $response->assertSessionHasErrors(['customer_email']);
    }

    // ========== EMAIL NOTIFICATION ==========

    public function test_confirmation_email_is_sent_to_non_anonymous_submitter(): void
    {
        Mail::fake();

        $data = $this->validAttentionData([
            'is_anonymous' => false,
            'customer_email' => 'citizen@example.com',
        ]);

        $this->post(route('peticiones.submit'), $data);

        Mail::assertSent(AttentionConfirmationMail::class, function ($mail) {
            return $mail->hasTo('citizen@example.com');
        });
    }

    public function test_no_confirmation_email_sent_for_anonymous_submission(): void
    {
        Mail::fake();

        $data = $this->validAnonymousAttentionData();

        $this->post(route('peticiones.submit'), $data);

        Mail::assertNotSent(AttentionConfirmationMail::class);
    }
}
