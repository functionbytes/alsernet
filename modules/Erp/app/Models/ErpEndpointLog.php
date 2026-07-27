<?php

namespace Modules\Erp\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Erp\Database\Factories\ErpEndpointLogFactory;

class ErpEndpointLog extends Model
{
    use HasFactory;

    public $timestamps = false; // Only created_at

    protected $fillable = [
        'endpoint_id',
        'user_id',
        'method',
        'url',
        'request_headers',
        'request_payload',
        'response_headers',
        'response_payload',
        'status_code',
        'execution_time',
        'success',
        'error_message',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_payload' => 'array',
        'response_headers' => 'array',
        'response_payload' => 'array',
        'status_code' => 'integer',
        'execution_time' => 'integer',
        'success' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Boot method to auto-set created_at
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            if (! $log->created_at) {
                $log->created_at = now();
            }
        });
    }

    /**
     * Get the endpoint that owns this log
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ErpEndpoint::class, 'endpoint_id');
    }

    /**
     * Get the user who triggered this request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope to get only successful logs
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope to get only failed logs
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope to filter by status code
     */
    public function scopeByStatusCode($query, int $statusCode)
    {
        return $query->where('status_code', $statusCode);
    }

    /**
     * Scope to get logs within a date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get execution time in seconds (formatted)
     */
    public function getExecutionTimeInSecondsAttribute(): ?string
    {
        if ($this->execution_time === null) {
            return null;
        }

        return number_format($this->execution_time / 1000, 3).'s';
    }

    /**
     * Check if response was successful (2xx status code)
     */
    public function isSuccessful(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }

    /**
     * Get HTTP status text
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status_code) {
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            default => 'Unknown',
        };
    }

    /**
     * Get badge color based on status code
     */
    public function getStatusColorAttribute(): string
    {
        if (! $this->status_code) {
            return 'secondary';
        }

        return match (true) {
            $this->status_code >= 200 && $this->status_code < 300 => 'success',
            $this->status_code >= 300 && $this->status_code < 400 => 'info',
            $this->status_code >= 400 && $this->status_code < 500 => 'warning',
            $this->status_code >= 500 => 'danger',
            default => 'secondary',
        };
    }

    protected static function newFactory(): ErpEndpointLogFactory
    {
        return ErpEndpointLogFactory::new();
    }
}
