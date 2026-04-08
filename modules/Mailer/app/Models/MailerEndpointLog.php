<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Mailer\Enums\EndpointLogStatus;

class MailerEndpointLog extends Model
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

    protected function casts(): array
    {
        return [
            'status' => EndpointLogStatus::class,
            'payload' => 'array',
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the associated email endpoint
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(MailerEndpoint::class, 'mailer_endpoint_id');
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeSuccess($query): void
    {
        $query->where('status', EndpointLogStatus::Success);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeFailed($query): void
    {
        $query->where('status', EndpointLogStatus::Failed);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePending($query): void
    {
        $query->where('status', EndpointLogStatus::Pending);
    }

    /**
     * @param  Builder<static>  $query
     * @param  string  $email  Email address to search
     */
    public function scopeSearchEmail($query, string $email): void
    {
        $query->where('recipient_email', 'like', "%{$email}%");
    }

    /**
     * @param  Builder<static>  $query
     * @param  string  $period  Period filter (24h, 7d, 30d)
     */
    public function scopePeriod($query, string $period): void
    {
        $query->where('created_at', '>=', match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subYear(),
        });
    }
}
