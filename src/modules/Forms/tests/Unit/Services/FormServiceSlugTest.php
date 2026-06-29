<?php

namespace Modules\Forms\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Forms\Models\Form;
use Modules\Forms\Services\FormService;
use Tests\TestCase;

class FormServiceSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_is_generated_from_name(): void
    {
        $slug = app(FormService::class)->generateUniqueSlug('Mi Formulario');

        $this->assertSame('mi-formulario', $slug);
    }

    public function test_slug_appends_counter_on_collision(): void
    {
        Form::factory()->create(['name' => 'Contacto', 'slug' => 'contacto']);
        Form::factory()->create(['name' => 'Contacto', 'slug' => 'contacto-1']);

        $slug = app(FormService::class)->generateUniqueSlug('Contacto');

        $this->assertSame('contacto-2', $slug);
    }

    public function test_slug_ignores_own_record_when_updating(): void
    {
        $form = Form::factory()->create(['name' => 'Original', 'slug' => 'original']);

        $slug = app(FormService::class)->generateUniqueSlug('Original', $form->id);

        $this->assertSame('original', $slug);
    }

    public function test_unicode_accents_are_normalized(): void
    {
        $slug = app(FormService::class)->generateUniqueSlug('Opinión de usuarios ñ');

        $this->assertSame('opinion-de-usuarios-n', $slug);
    }
}
