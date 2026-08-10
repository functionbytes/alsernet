<?php

namespace Modules\HelpdeskEmailLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLogOpen extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_log_id',
        'ip',
        'user_agent',
        'opened_at',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
        ];
    }

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class);
    }
}
