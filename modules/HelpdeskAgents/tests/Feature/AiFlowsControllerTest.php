<?php

namespace Modules\HelpdeskAgents\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskAgents\Http\Controllers\Managers\AiAgentFlowsController;
use Modules\HelpdeskAgents\Models\AiAgentFlow;
use Tests\TestCase;

class AiFlowsControllerTest extends TestCase
{
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(AiAgentFlowsController::class));
    }

    public function test_model_class_exists(): void
    {
        $this->assertTrue(class_exists(AiAgentFlow::class));
    }

    public function test_index_route_registered(): void
    {
        $this->assertTrue(Route::has('helpdesk.ai.flows.index'));
    }

    public function test_store_route_registered(): void
    {
        $this->assertTrue(Route::has('helpdesk.ai.flows.store'));
    }

    public function test_destroy_route_registered(): void
    {
        $this->assertTrue(Route::has('helpdesk.ai.flows.destroy'));
    }
}
