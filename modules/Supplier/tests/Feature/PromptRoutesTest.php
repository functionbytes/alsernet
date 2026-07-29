<?php

namespace Modules\Supplier\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierPromptsController;
use Tests\TestCase;

class PromptRoutesTest extends TestCase
{
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(SupplierPromptsController::class));
    }

    public function test_settings_prompts_index_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.prompts.index'));
    }

    public function test_settings_prompts_create_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.prompts.create'));
    }

    public function test_settings_prompts_store_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.prompts.store'));
    }

    public function test_settings_prompts_show_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.prompts.show'));
    }

    public function test_settings_prompts_edit_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.prompts.edit'));
    }

    public function test_settings_prompts_update_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.prompts.update'));
    }

    public function test_settings_prompts_destroy_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.prompts.destroy'));
    }

    public function test_settings_prompts_toggle_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.prompts.toggle'));
    }

    public function test_settings_prompts_test_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.prompts.test'));
    }

    public function test_settings_prompts_duplicate_route_exists(): void
    {
        $this->assertTrue(Route::has('settings.suppliers.prompts.duplicate'));
    }
}
