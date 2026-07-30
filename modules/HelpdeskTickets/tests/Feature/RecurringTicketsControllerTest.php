<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\RecurringTicketsController;
use Modules\HelpdeskTickets\Models\RecurringTicket;
use Tests\TestCase;

class RecurringTicketsControllerTest extends TestCase
{
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(RecurringTicketsController::class));
    }

    public function test_model_class_exists(): void
    {
        $this->assertTrue(class_exists(RecurringTicket::class));
    }

    public function test_index_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.recurring-tickets.index'));
    }

    public function test_store_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.recurring-tickets.store'));
    }

    public function test_destroy_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.recurring-tickets.destroy'));
    }
}
