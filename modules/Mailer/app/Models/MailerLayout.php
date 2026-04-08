<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Mailer\Traits\HasUid;

class MailerLayout extends Model
{
    use HasUid;

    protected $table = 'mailer_layouts';

    protected $fillable = [
        'name',
        'alias',
        'code',
        'type',
        'group_name',
        'is_protected',
        'is_enabled',
    ];

    protected $casts = [
        'is_protected' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    /**
     * Get all translations for this layout
     */
    public function translations(): HasMany
    {
        return $this->hasMany(MailerLayoutLang::class, 'layout_id', 'id');
    }

    /**
     * Get translation for a specific language
     */
    public function translate(?int $langId = null): ?MailerLayoutLang
    {
        if ($langId === null) {
            // Try to get from session, request, or default to first available
            $langId = session('lang_id') ?? request()->get('lang_id') ?? 1;
        }

        return $this->translations()->where('lang_id', $langId)->first()
            ?? $this->translations()->first(); // Fallback to first available translation
    }

    /**
     * Magic getter for subject (backwards compatibility)
     */
    public function getSubjectAttribute(): ?string
    {
        return $this->translate()?->subject;
    }

    /**
     * Magic getter for content (backwards compatibility)
     */
    public function getContentAttribute(): ?string
    {
        return $this->translate()?->content;
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeAlias($query, $alias)
    {
        return $query->where('alias', $alias);
    }

    public function scopeCode($query, $code)
    {
        return $query->where('code', $code);
    }
}
