<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketTemplatesController;
use Modules\HelpdeskTickets\Models\TicketTemplate;
use Tests\TestCase;

class TicketTemplatesControllerTest extends TestCase
{
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketTemplatesController::class));
    }

    public function test_model_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketTemplate::class));
    }

    public function test_index_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.ticket-templates.index'));
    }

    public function test_store_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.ticket-templates.store'));
    }

    public function test_destroy_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.ticket-templates.destroy'));
    }
}
