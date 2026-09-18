<?php

namespace Modules\Forms\Tests\Feature;

use Illuminate\Support\Str;
use Modules\Forms\Models\FormAccessToken;
use Modules\Forms\Tests\TestCase;

class FormAccessTest extends TestCase
{
    // ========== VALID TOKEN ==========

    public function test_valid_token_redirects_to_form_embed(): void
    {
        $form = $this->createActiveForm(['access_control' => 'public']);

        $token = FormAccessToken::create([
            'form_id' => $form->id,
            'token' => Str::random(64),
            'max_uses' => 5,
            'times_used' => 0,
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->get(route('forms.public.access', $token->token));

        $response->assertRedirect(route('forms.public.embed', $form->slug));
    }

    public function test_valid_token_sets_session_access(): void
    {
        $form = $this->createActiveForm(['access_control' => 'public']);

        $token = FormAccessToken::create([
            'form_id' => $form->id,
            'token' => Str::random(64),
            'max_uses' => 5,
            'times_used' => 0,
            'expires_at' => null,
        ]);

        $this->get(route('forms.public.access', $token->token));

        $this->assertDatabaseHas('form_access_tokens', [
            'id' => $token->id,
            'times_used' => 1,
        ]);
    }

    // ========== INVALID TOKEN ==========

    public function test_nonexistent_token_returns_404(): void
    {
        $response = $this->get(route('forms.public.access', 'this-token-does-not-exist'));

        $response->assertNotFound();
    }

    public function test_exhausted_token_returns_403(): void
    {
        $form = $this->createActiveForm(['access_control' => 'public']);

        $token = FormAccessToken::create([
            'form_id' => $form->id,
            'token' => Str::random(64),
            'max_uses' => 3,
            'times_used' => 3,
            'expires_at' => null,
        ]);

        $response = $this->get(route('forms.public.access', $token->token));

        $response->assertForbidden();
    }

    // ========== EXPIRED TOKEN ==========

    public function test_expired_token_returns_403(): void
    {
        $form = $this->createActiveForm(['access_control' => 'public']);

        $token = FormAccessToken::create([
            'form_id' => $form->id,
            'token' => Str::random(64),
            'max_uses' => 10,
            'times_used' => 0,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->get(route('forms.public.access', $token->token));

        $response->assertForbidden();
    }

    public function test_token_without_expiry_is_valid(): void
    {
        $form = $this->createActiveForm(['access_control' => 'public']);

        $token = FormAccessToken::create([
            'form_id' => $form->id,
            'token' => Str::random(64),
            'max_uses' => 1,
            'times_used' => 0,
            'expires_at' => null,
        ]);

        $response = $this->get(route('forms.public.access', $token->token));

        $response->assertRedirect();
        $response->assertStatus(302);
    }
}
