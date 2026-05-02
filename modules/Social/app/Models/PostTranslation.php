<?php

namespace Modules\Social\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostTranslation extends Model
{
    protected $table = 'social_post_translations';

    protected $fillable = [
        'post_id',
        'language',
        'content',
        'translation_provider',
        'confidence_score',
        'translated_by',
        'translated_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:2',
            'translated_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function translator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'translated_by');
    }

    public function isAutomatic(): bool
    {
        return in_array($this->translation_provider, ['google', 'deepl', 'openai']);
    }

    public function isManual(): bool
    {
        return $this->translation_provider === 'manual';
    }
}
