<?php

namespace Modules\Chat\Models\Channels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappWebhookLog extends Model
{
    protected $table = 'whatsapp_webhook_logs';

    protected $fillable = [
        'whatsapp_id',
        'phone_number',
        'event_type',
        'payload',
        'processed',
        'error',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
    ];

    /**
     * Get the WhatsApp channel this log belongs to.
     */
    public function whatsapp(): BelongsTo
    {
        return $this->belongsTo(Whatsapp::class);
    }

    /**
     * Scope: Get unprocessed logs
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    /**
     * Scope: Get logs with errors
     */
    public function scopeWithErrors($query)
    {
        return $query->whereNotNull('error');
    }

    /**
     * Scope: Get logs for specific event type
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Mark as processed
     */
    public function markAsProcessed(): void
    {
        $this->update(['processed' => true]);
    }

    /**
     * Mark as processed with error
     */
    public function markAsError(string $error): void
    {
        $this->update([
            'processed' => true,
            'error' => $error,
        ]);
    }
}
