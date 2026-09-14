<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketSlaPoliciesController;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Tests\TestCase;

class TicketSlaPoliciesControllerTest extends TestCase
{
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketSlaPoliciesController::class));
    }

    public function test_model_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketSlaPolicy::class));
    }

    public function test_index_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-sla-policies.index'));
    }

    public function test_store_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-sla-policies.store'));
    }

    public function test_destroy_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-sla-policies.destroy'));
    }
}
