<?php

namespace Modules\Forms\Tests\Feature;

use Modules\Forms\Tests\TestCase;

class FormPreviewHtmlTest extends TestCase
{
    public function test_authorized_user_gets_preview_html(): void
    {
        $user = $this->createUser(['Forms.forms.index']);
        $form = $this->createActiveForm();
        $this->createField($form, ['label' => 'Nombre', 'key' => 'nombre', 'type' => 'text', 'is_visible' => true]);

        $response = $this->actingAs($user)->get(route('forms.internal.preview-html', $form));

        $response->assertOk();
        $response->assertJsonStructure(['success', 'data' => ['id', 'slug', 'name', 'shortcode', 'fields_count', 'html']]);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.id', $form->id);
    }

    public function test_unauthorized_user_is_rejected(): void
    {
        $user = $this->createUser();
        $form = $this->createActiveForm();

        $response = $this->actingAs($user)->get(route('forms.internal.preview-html', $form));

        $response->assertForbidden();
    }

    public function test_preview_respects_display_override(): void
    {
        $user = $this->createUser(['Forms.forms.index']);
        $form = $this->createActiveForm();

        $response = $this->actingAs($user)
            ->get(route('forms.internal.preview-html', $form).'?display=popup');

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }
}
