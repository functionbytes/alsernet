<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ReviewAiSetting extends Model
{
    protected $table = 'review_ai_settings';

    protected $fillable = [
        'provider',
        'api_key',
        'model',
        'is_enabled',
        'tone',
        'language',
        'max_tokens',
        'custom_instructions',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'max_tokens' => 'integer',
        ];
    }

    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                try {
                    return decrypt($value);
                } catch (\Exception) {
                    return null;
                }
            },
            set: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                return encrypt($value);
            }
        );
    }

    public static function getInstance(): ?static
    {
        return static::first();
    }

    /**
     * @return array<string, string>
     */
    public static function getModelsForProvider(string $provider): array
    {
        return match ($provider) {
            'anthropic' => [
                'claude-opus-4-6' => 'Claude Opus 4.6',
                'claude-sonnet-4-6' => 'Claude Sonnet 4.6',
                'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5',
            ],
            'openai' => [
                'gpt-4o' => 'GPT-4o',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            ],
            default => [],
        };
    }
}
