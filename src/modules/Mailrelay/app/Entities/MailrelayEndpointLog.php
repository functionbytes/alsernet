<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailrelayEndpointLog extends Model
{
    protected $table = 'mailrelay_endpoint_logs';

    public $timestamps = false;

    protected $fillable = [
        'mailrelay_endpoint_id',
        'request_ip',
        'request_method',
        'request_payload',
        'response_status',
        'response_body',
        'error_message',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'mailrelay_endpoint_id' => 'integer',
            'request_payload' => 'array',
            'response_status' => 'integer',
            'executed_at' => 'datetime',
        ];
    }

    /**
     * Get the endpoint that owns this log.
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(MailrelayEndpoint::class, 'mailrelay_endpoint_id');
    }

    /**
     * Check if this log represents a successful request.
     */
    public function isSuccessful(): bool
    {
        return $this->response_status >= 200 && $this->response_status < 300;
    }

    /**
     * Check if this log represents a failed request.
     */
    public function isFailed(): bool
    {
        return ! $this->isSuccessful();
    }

    /**
     * Scope to get only successful logs.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('response_status', [200, 299]);
    }

    /**
     * Scope to get only failed logs.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeFailed($query)
    {
        return $query->where(function ($q) {
            $q->where('response_status', '<', 200)
                ->orWhere('response_status', '>=', 300);
        });
    }

    /**
     * Scope to filter by date range.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('executed_at', [$from, $to]);
    }
}
