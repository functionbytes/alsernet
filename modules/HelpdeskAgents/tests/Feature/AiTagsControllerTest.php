<?php

namespace Modules\HelpdeskAgents\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskAgents\Http\Controllers\Managers\Settings\AgentSettingsController;
use Modules\HelpdeskAgents\Models\AiAgentTag;
use Tests\TestCase;

class AiTagsControllerTest extends TestCase
{
    public function test_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(AgentSettingsController::class));
    }

    public function test_model_class_exists(): void
    {
        $this->assertTrue(class_exists(AiAgentTag::class));
    }

    public function test_index_route_registered(): void
    {
        $this->assertTrue(Route::has('helpdesk.ai.tags.index'));
    }

    public function test_store_route_registered(): void
    {
        $this->assertTrue(Route::has('helpdesk.ai.tags.store'));
    }

    public function test_destroy_route_registered(): void
    {
        $this->assertTrue(Route::has('helpdesk.ai.tags.destroy'));
    }
}
