<?php

namespace Modules\HelpdeskSocial\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpdeskSocial\Database\Factories\SocialTemplateFactory;

class SocialTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'helpdesk_social_templates';

    protected $fillable = [
        'name',
        'description',
        'platform',
        'body',
        'variables',
        'quick_replies',
        'category',
        'is_active',
        'is_default',
        'usage_count',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'quick_replies' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlatform($query, ?string $platform)
    {
        return $query->when($platform, fn ($q) => $q->where(function ($sq) use ($platform) {
            $sq->whereNull('platform')->orWhere('platform', $platform);
        }));
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Render template with variable substitution.
     *
     * @param  array<string, mixed>  $data
     */
    public function render(array $data): string
    {
        $body = $this->body;

        foreach ($data as $key => $value) {
            $escaped = e((string) $value);
            $body = str_replace('{{'.$key.'}}', $escaped, $body);
            $body = str_replace('{{ '.$key.' }}', $escaped, $body);
        }

        return $body;
    }

    protected static function newFactory(): SocialTemplateFactory
    {
        return SocialTemplateFactory::new();
    }
}
