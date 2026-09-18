<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketCannedRepliesController;
use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Tests\TestCase;

class TicketCannedRepliesControllerTest extends TestCase
{
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketCannedRepliesController::class));
    }

    public function test_model_class_exists(): void
    {
        $this->assertTrue(class_exists(TicketCannedReply::class));
    }

    public function test_index_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-canned-replies.index'));
    }

    public function test_store_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-canned-replies.store'));
    }

    public function test_destroy_route_registered(): void
    {
        $this->assertTrue(Route::has('manager.helpdesk.settings.ticket-canned-replies.destroy'));
    }
}
