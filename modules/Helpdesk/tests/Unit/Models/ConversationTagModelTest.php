<?php

namespace Modules\Helpdesk\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Helpdesk\Models\ConversationTag;
use Tests\TestCase;

class ConversationTagModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_search_does_not_override_previous_filters(): void
    {
        ConversationTag::factory()->create(['name' => 'urgente', 'description' => 'Alta prioridad']);
        ConversationTag::factory()->create(['name' => 'seguimiento', 'description' => 'Revisar']);

        $results = ConversationTag::query()
            ->where('name', 'like', '%NonExistent%')
            ->search('urgente')
            ->get();

        $this->assertCount(0, $results);
    }
}
