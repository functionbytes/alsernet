<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketViewsController;
use Modules\HelpdeskTickets\Models\TicketView;
use Tests\TestCase;

class TicketViewsControllerTest extends TestCase
{
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketViewsController::class));
    }

    public function test_model_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketView::class));
    }

    public function test_index_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-views.index'));
    }

    public function test_store_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-views.store'));
    }

    public function test_destroy_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-views.destroy'));
    }
}
