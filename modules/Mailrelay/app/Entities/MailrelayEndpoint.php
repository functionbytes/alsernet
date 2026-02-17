<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailrelayEndpoint extends Model
{
    protected $table = 'mailrelay_endpoints';

    protected $fillable = [
        'name',
        'endpoint_key',
        'description',
        'template_id',
        'status',
        'api_key',
        'rate_limit',
        'allowed_ips',
        'webhook_url',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'template_id' => 'integer',
            'api_key' => 'encrypted',
            'rate_limit' => 'integer',
            'allowed_ips' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Get the template associated with this endpoint.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    /**
     * Get the logs for this endpoint.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(MailrelayEndpointLog::class, 'mailrelay_endpoint_id');
    }

    /**
     * Get recent logs (last 100).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentLogs(int $limit = 100)
    {
        return $this->logs()
            ->orderBy('executed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if an IP is allowed to access this endpoint.
     */
    public function isIpAllowed(string $ip): bool
    {
        // If no IPs are configured, allow all
        if (empty($this->allowed_ips)) {
            return true;
        }

        return in_array($ip, $this->allowed_ips);
    }

    /**
     * Verify if the provided API key matches this endpoint's key.
     */
    public function verifyApiKey(string $apiKey): bool
    {
        return hash_equals($this->api_key, $apiKey);
    }

    /**
     * Generate a new API key for this endpoint.
     */
    public function generateApiKey(): string
    {
        $newKey = bin2hex(random_bytes(32));
        $this->api_key = $newKey;
        $this->save();

        return $newKey;
    }

    /**
     * Update the last used timestamp.
     */
    public function touchLastUsed(): bool
    {
        $this->last_used_at = now();

        return $this->save();
    }

    /**
     * Scope to filter only active endpoints.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by template.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTemplate($query, int $templateId)
    {
        return $query->where('template_id', $templateId);
    }
}
