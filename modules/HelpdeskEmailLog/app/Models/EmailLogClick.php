<?php

namespace Modules\HelpdeskEmailLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLogClick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_log_link_id',
        'ip',
        'user_agent',
        'clicked_at',
    ];

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(EmailLogLink::class, 'email_log_link_id');
    }
}
