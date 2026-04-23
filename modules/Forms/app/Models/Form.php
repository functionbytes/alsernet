<?php

namespace Modules\Forms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Modules\Forms\Database\Factories\FormFactory;
use Modules\Forms\Services\FormPageCacheInvalidator;
use Modules\Seo\Traits\HasSeo;

class Form extends Model
{
    use HasFactory;
    use HasSeo;
    use SoftDeletes;

    protected $table = 'forms';

    protected static function newFactory(): Factory
    {
        return FormFactory::new();
    }

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'status',
        'success_message',
        'redirect_url',
        'admin_notification_email',
        'send_confirmation',
        'email_field_key',
        'admin_template_id',
        'confirmation_template_id',
        'confirmation_subject',
        'confirmation_message',
        'is_active',
        'allow_multiple',
        'honeypot_enabled',
        'captcha_enabled',
        'is_password_protected',
        'password',
        'expires_at',
        'max_submissions',
        'prevent_duplicate_email',
        'access_control',
        'allowed_roles',
        'retention_days',
        'limit_per_user',
        'webhook_url',
        'webhook_secret',
        'is_multi_step',
        'steps_config',
        'theme',
        'custom_css',
        'custom_js',
        'style_config',
        'submit_button_text',
        'button_position',
        'button_size',
        'button_variant',
        'button_icon',
        'success_animation',
        'progress_bar_style',
        'floating_label',
    ];

    protected function casts(): array
    {
        return [
            'send_confirmation' => 'boolean',
            'is_active' => 'boolean',
            'allow_multiple' => 'boolean',
            'honeypot_enabled' => 'boolean',
            'captcha_enabled' => 'boolean',
            'is_password_protected' => 'boolean',
            'prevent_duplicate_email' => 'boolean',
            'limit_per_user' => 'boolean',
            'is_multi_step' => 'boolean',
            'floating_label' => 'boolean',
            'allowed_roles' => 'array',
            'steps_config' => 'array',
            'style_config' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FormCategory::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FormVersion::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FormFollowUp::class);
    }

    public function conditionalEmails(): HasMany
    {
        return $this->hasMany(FormConditionalEmail::class);
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(FormAccessToken::class);
    }

    public function abandonTracking(): HasMany
    {
        return $this->hasMany(FormAbandonTracking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMultiStep($query)
    {
        return $query->where('is_multi_step', true);
    }

    public function scopePublicAccess($query)
    {
        return $query->where('access_control', 'public');
    }

    public function getShortcodeTagAttribute(): string
    {
        return "[form id=\"{$this->id}\"]";
    }

    public function getShortcodeSlugTagAttribute(): string
    {
        return "[form slug=\"{$this->slug}\"]";
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getIsFullAttribute(): bool
    {
        if (! $this->max_submissions) {
            return false;
        }

        return $this->getSubmissionsCount() >= $this->max_submissions;
    }

    public function isAccessibleBy(?User $user): bool
    {
        if (! $this->is_active || $this->is_expired) {
            return false;
        }

        if ($this->access_control === 'public') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($this->access_control === 'authenticated') {
            return true;
        }

        $allowedRoles = $this->allowed_roles ?? [];

        if (empty($allowedRoles)) {
            return true;
        }

        return $user->hasAnyRole($allowedRoles);
    }

    public function getSubmissionsCount(): int
    {
        $ttl = (int) config('forms.cache.counters_ttl_seconds', 60);

        return (int) Cache::remember(
            "forms:{$this->id}:submissions_count",
            $ttl,
            fn () => $this->submissions()->count(),
        );
    }

    public function getUnreadCount(): int
    {
        $ttl = (int) config('forms.cache.counters_ttl_seconds', 60);

        return (int) Cache::remember(
            "forms:{$this->id}:unread_count",
            $ttl,
            fn () => $this->submissions()->where('is_read', false)->count(),
        );
    }

    public function flushCountersCache(): void
    {
        Cache::forget("forms:{$this->id}:submissions_count");
        Cache::forget("forms:{$this->id}:unread_count");
    }

    public function flushShortcodeCache(): void
    {
        self::flushShortcodeCacheFor($this->id, $this->slug);

        if ($this->wasChanged('slug') && ($original = $this->getOriginal('slug'))) {
            self::flushShortcodeCacheFor($this->id, $original);
        }
    }

    public static function flushShortcodeCacheFor(int $id, ?string $slug): void
    {
        if (self::cacheDriverSupportsTags()) {
            // Solo flush del tag específico de este form
            Cache::tags(["forms:shortcode:{$id}"])->flush();

            return;
        }

        Cache::forget("forms:active:{$id}");

        if ($slug) {
            Cache::forget("forms:active:slug:{$slug}");
        }
    }

    public static function cacheDriverSupportsTags(): bool
    {
        return in_array(config('cache.default'), ['redis', 'memcached'], true);
    }

    protected static function booted(): void
    {
        static::saved(function (self $form) {
            $form->flushShortcodeCache();
            app(FormPageCacheInvalidator::class)->invalidate($form);
        });

        static::deleted(function (self $form) {
            $form->flushCountersCache();
            $form->flushShortcodeCache();
            app(FormPageCacheInvalidator::class)->invalidate($form);
        });
    }
}
