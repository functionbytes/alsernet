<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Modules\HelpdeskChatFlow\Http\Requests\UpdateChatFlowRequest;
use Tests\TestCase;

class UpdateChatFlowRequestTest extends TestCase
{
    public function test_settings_toggles_survive_validation(): void
    {
        $rules = (new UpdateChatFlowRequest)->rules();

        $validated = Validator::make([
            'name' => 'Flow',
            'trigger_type' => 'manual',
            'trigger_conditions' => [
                'multilingual' => true,
                'sentiment_escalation' => true,
                'escape_enabled' => false,
                'handoff_summary' => true,
                'ab_variant_id' => 9,
                'ab_split' => 30,
            ],
        ], $rules)->validated();

        $conditions = $validated['trigger_conditions'];

        $this->assertTrue($conditions['handoff_summary']);
        $this->assertTrue($conditions['multilingual']);
        $this->assertTrue($conditions['sentiment_escalation']);
        $this->assertFalse($conditions['escape_enabled']);
        $this->assertSame(9, $conditions['ab_variant_id']);
        $this->assertSame(30, $conditions['ab_split']);
    }

    public function test_invalid_ab_split_is_rejected(): void
    {
        $rules = (new UpdateChatFlowRequest)->rules();

        $validator = Validator::make([
            'trigger_conditions' => ['ab_split' => 250],
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('trigger_conditions.ab_split', $validator->errors()->toArray());
    }
}
