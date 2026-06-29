<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpdeskDataAccessLog extends Model
{
    protected $table = 'helpdesk_data_access_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email_accessed',
        'source',
        'action',
        'ip_address',
        'user_agent',
        'request_id',
        'accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
