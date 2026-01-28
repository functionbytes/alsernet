<?php

namespace Modules\Mailing\Models\Mailing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailingEndpointLog extends Model
{
    protected $table = 'mailer_endpoint_logs';

    protected $fillable = [
        'mailer_endpoint_id',
        'payload',
        'status',
        'error_message',
        'recipient_email',
        'mailer_subject',
        'sent_at',
        'job_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the associated email endpoint
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(MailingEndpoint::class, 'mailer_endpoint_id');
    }
}
