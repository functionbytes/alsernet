<?php

namespace Modules\Erp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Modules\Erp\Database\Factories\ErpCredentialFactory;

class ErpCredential extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'auth_type',
        'username',
        'password',
        'token',
        'api_key',
        'api_key_header',
        'custom_headers',
        'is_active',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'custom_headers' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'token',
        'api_key',
    ];

    /**
     * Get the endpoint this credential belongs to
     */
    public function endpoint()
    {
        return $this->belongsTo(ErpEndpoint::class, 'endpoint_id');
    }

    /**
     * Get all endpoints using this credential as the primary
     */
    public function endpoints()
    {
        return $this->hasMany(ErpEndpoint::class, 'credential_id');
    }

    /**
     * Scope to get only active credentials
     */
    public function scopeForAccount($query, ?int $accountId)
    {
        if ($accountId) {
            return $query->where('account_id', $accountId);
        }

        return $query->whereNull('account_id');
    }

    /**
     * Encrypt password before saving
     */
    public function setPasswordAttribute(?string $value): void
    {
        if ($value) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt password when accessing
     */
    public function getPasswordAttribute(?string $value): ?string
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Encrypt token before saving
     */
    public function setTokenAttribute(?string $value): void
    {
        if ($value) {
            $this->attributes['token'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt token when accessing
     */
    public function getTokenAttribute(?string $value): ?string
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Encrypt API key before saving
     */
    public function setApiKeyAttribute(?string $value): void
    {
        if ($value) {
            $this->attributes['api_key'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt API key when accessing
     */
    public function getApiKeyAttribute(?string $value): ?string
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Check if credential is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Mark credential as used
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get authorization headers based on auth type
     */
    public function getAuthHeaders(): array
    {
        $headers = [];

        switch ($this->auth_type) {
            case 'basic':
                if ($this->username && $this->password) {
                    $credentials = base64_encode($this->username.':'.$this->password);
                    $headers['Authorization'] = 'Basic '.$credentials;
                }
                break;

            case 'bearer':
                if ($this->token) {
                    $headers['Authorization'] = 'Bearer '.$this->token;
                }
                break;

            case 'api_key':
                if ($this->api_key) {
                    $headers[$this->api_key_header] = $this->api_key;
                }
                break;

            case 'custom':
                if ($this->custom_headers) {
                    $headers = array_merge($headers, $this->custom_headers);
                }
                break;

            case 'none':
            default:
                // No authentication
                break;
        }

        return $headers;
    }

    /**
     * Scope to get only active credentials
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get non-expired credentials
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    protected static function newFactory(): ErpCredentialFactory
    {
        return ErpCredentialFactory::new();
    }
}
