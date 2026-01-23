<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MailerEndpoint extends Model
{
    use SoftDeletes;

    protected $table = 'mailer_endpoints';

    protected $fillable = [
        'name',
        'slug',
        'source',
        'type',
        'description',
        'mailer_template_id',
        'lang_id',
        'expected_variables',
        'required_variables',
        'variable_mappings',
        'is_active',
        'api_token',
        'requests_count',
        'last_request_at',
    ];

    protected $casts = [
        'expected_variables' => 'array',
        'required_variables' => 'array',
        'variable_mappings' => 'array',
        'is_active' => 'boolean',
        'last_request_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Generate a unique API token
     */
    public static function generateToken(): string
    {
        return hash('sha256', uniqid(mt_rand(), true));
    }

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->api_token)) {
                $model->api_token = self::generateToken();
            }
        });
    }

    /**
     * Get the associated email template
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MailerTemplate::class, 'mailer_template_id');
    }

    /**
     * Get the associated language
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo('App\Models\Lang', 'lang_id');
    }

    /**
     * Get the associated language (alias)
     */
    public function lang(): BelongsTo
    {
        return $this->belongsTo('App\Models\Lang', 'lang_id');
    }

    /**
     * Get the endpoint logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(MailerEndpointLog::class);
    }

    /**
     * Get successful logs
     */
    public function successLogs(): HasMany
    {
        return $this->logs()->where('status', 'success');
    }

    /**
     * Get failed logs
     */
    public function failedLogs(): HasMany
    {
        return $this->logs()->where('status', 'failed');
    }
}
