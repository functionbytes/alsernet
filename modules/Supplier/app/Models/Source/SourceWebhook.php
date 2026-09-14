<?php

namespace Modules\Supplier\Models\Source;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Supplier\Database\Factories\Source\SourceWebhookFactory;

class SourceWebhook extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_source_webhooks';

    protected $fillable = [
        'source_id',
        'name',
        'description',
        'endpoint_path',
        'secret_key',
        'events',
        'payload_mapping',
        'processing_mode',
        'batch_size',
        'batch_window_seconds',
        'auth_type',
        'auth_config',
        'is_enabled',
        'last_received_at',
        'total_received',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'payload_mapping' => 'array',
            'secret_key' => 'encrypted',
            'auth_config' => 'encrypted:array',
            'is_enabled' => 'boolean',
            'last_received_at' => 'datetime',
            'total_received' => 'integer',
            'batch_size' => 'integer',
            'batch_window_seconds' => 'integer',
        ];
    }

    protected static function newFactory(): SourceWebhookFactory
    {
        return SourceWebhookFactory::new();
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeByEndpoint($query, string $endpointPath)
    {
        return $query->where('endpoint_path', $endpointPath);
    }

    public function isSyncProcessing(): bool
    {
        return $this->processing_mode === 'sync';
    }

    public function isAsyncProcessing(): bool
    {
        return $this->processing_mode === 'async';
    }

    public function isBatchProcessing(): bool
    {
        return $this->processing_mode === 'batch';
    }

    public function supportsEvent(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    public function validateSignature(string $signature, string $payload): bool
    {
        if (empty($this->secret_key)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->secret_key);

        return hash_equals($expectedSignature, $signature);
    }

    public function validateBasicAuth(string $username, string $password): bool
    {
        if ($this->auth_type !== 'basic' || ! $this->auth_config) {
            return false;
        }

        $expectedUsername = (string) ($this->auth_config['username'] ?? '');
        $expectedPassword = (string) ($this->auth_config['password'] ?? '');

        if ($expectedUsername === '' || $expectedPassword === '') {
            return false;
        }

        return hash_equals($expectedUsername, $username)
            && hash_equals($expectedPassword, $password);
    }

    public function validateBearerToken(string $token): bool
    {
        if ($this->auth_type !== 'bearer' || ! $this->auth_config) {
            return false;
        }

        $expectedToken = (string) ($this->auth_config['token'] ?? '');

        if ($expectedToken === '') {
            return false;
        }

        return hash_equals($expectedToken, $token);
    }

    public function mapPayload(array $payload): array
    {
        $mapped = [];

        foreach ($this->payload_mapping as $targetField => $sourcePath) {
            $mapped[$targetField] = $this->extractFromPath($payload, $sourcePath);
        }

        return $mapped;
    }

    protected function extractFromPath(array $data, string $path): mixed
    {
        // Support JSONPath-like syntax: $.data.id
        $path = ltrim($path, '$.');
        $keys = explode('.', $path);

        $value = $data;
        foreach ($keys as $key) {
            if (! is_array($value) || ! isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    public function recordReceived(): void
    {
        $this->increment('total_received');
        $this->update(['last_received_at' => now()]);
    }

    public function getFullEndpointUrl(): string
    {
        return rtrim(config('app.url'), '/').'/'.ltrim($this->endpoint_path, '/');
    }

    public function generateSecretKey(): string
    {
        $this->secret_key = Str::random(64);
        $this->save();

        return $this->secret_key;
    }

    public function regenerateSecretKey(): string
    {
        return $this->generateSecretKey();
    }
}
