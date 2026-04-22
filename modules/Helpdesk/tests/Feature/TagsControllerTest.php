<?php

namespace Modules\Helpdesk\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Managers\Settings\TagsController;
use Modules\Helpdesk\Models\ConversationTag;
use Tests\TestCase;

class TagsControllerTest extends TestCase
{
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(TagsController::class));
    }

    public function test_model_class_exists(): void
    {
        $this->assertTrue(class_exists(ConversationTag::class));
    }

    public function test_index_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.tickets.tags.index'));
    }

    public function test_store_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.tickets.tags.store'));
    }

    public function test_destroy_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.tickets.tags.destroy'));
    }
}
