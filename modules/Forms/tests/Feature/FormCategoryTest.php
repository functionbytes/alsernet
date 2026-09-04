<?php

namespace Modules\Forms\Tests\Feature;

use Modules\Forms\Models\FormCategory;
use Modules\Forms\Tests\TestCase;

class FormCategoryTest extends TestCase
{
    public function test_admin_can_list_categories(): void
    {
        $user = $this->createUser(['Forms.categories.manage']);
        $category = FormCategory::factory()->create();
        $this->createForm(['category_id' => $category->id]);

        $response = $this->actingAs($user)->get(route('settings.forms.categories.index'));

        $response->assertOk();
    }

    public function test_admin_can_create_category(): void
    {
        $user = $this->createUser(['Forms.categories.manage']);

        $response = $this->actingAs($user)->post(route('settings.forms.categories.store'), [
            'name' => 'Contacto',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('settings.forms.categories.index'));

        $this->assertDatabaseHas('form_categories', ['name' => 'Contacto']);
    }
}
