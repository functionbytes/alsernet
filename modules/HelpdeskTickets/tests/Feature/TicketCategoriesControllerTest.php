<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketCategoriesController;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Tests\TestCase;

class TicketCategoriesControllerTest extends TestCase
{
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketCategoriesController::class));
    }

    public function test_model_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketCategory::class));
    }

    public function test_index_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-categories.index'));
    }

    public function test_store_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-categories.store'));
    }

    public function test_destroy_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-categories.destroy'));
    }
}
