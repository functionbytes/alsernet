<?php

namespace Modules\Campaign\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignAutomationRule extends Model
{
    use HasFactory;
    use HasUid;

    protected $table = 'campaign_automation_rules';

    protected $fillable = [
        'uid',
        'name',
        'trigger_event',
        'condition',
        'condition_value',
        'action',
        'action_value',
        'delay_minutes',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'condition_value' => 'array',
            'action_value' => 'array',
            'enabled' => 'boolean',
            'delay_minutes' => 'integer',
        ];
    }
}
