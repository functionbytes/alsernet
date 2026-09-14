<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_brands';

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'primary_color',
        'logo_url',
        'email_from_name',
        'email_from_address',
        'widget_token',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function inboxes(): HasMany
    {
        return $this->hasMany(Inbox::class, 'brand_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'brand_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Detect a brand from a request domain or widget token.
     */
    public static function findByDomain(string $host): ?self
    {
        return self::active()->where('domain', $host)->first();
    }

    public static function findByWidgetToken(string $token): ?self
    {
        return self::active()->where('widget_token', $token)->first();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $brand): void {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }

            if (empty($brand->widget_token)) {
                $brand->widget_token = Str::random(32);
            }
        });
    }
}
