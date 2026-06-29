<?php

namespace Modules\Remarketing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Mailer\Models\MailerTemplate;

class AutomationStep extends Model
{
    use HasFactory;

    protected $table = 'remarketing_automation_steps';

    protected $fillable = [
        'automation_id',
        'sort_order',
        'type',
        'config',
        'mailer_template_id',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function mailerTemplate(): BelongsTo
    {
        return $this->belongsTo(MailerTemplate::class, 'mailer_template_id');
    }
}
