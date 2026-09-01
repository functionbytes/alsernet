<?php

namespace Modules\HelpdeskEmailLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskEmailLog\Enums\EmailOpenSource;

class EmailLogOpen extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_log_id',
        'source',
        'ip',
        'user_agent',
        'opened_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => EmailOpenSource::class,
            'opened_at' => 'datetime',
        ];
    }

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class);
    }
}
