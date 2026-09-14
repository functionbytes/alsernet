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

    protected function casts(): array
    {
        return [
            'is_protected' => 'boolean',
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * Get all translations for this layout
     */
    public function translations(): HasMany
    {
        return $this->hasMany(MailerLayoutLang::class, 'layout_id', 'id');
    }

    /**
     * Get translation for a specific language.
     * Si no se pasa $langId, usa la session como hint y si no, 1.
     * Reutiliza la relación ya cargada cuando existe para evitar queries extra.
     */
    public function translate(?int $langId = null): ?MailerLayoutLang
    {
        $langId ??= (int) (session('lang_id') ?? MailerLang::resolveDefaultId());

        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('lang_id', $langId)
                ?? $this->translations->first();
        }

        return $this->translations()->where('lang_id', $langId)->first()
            ?? $this->translations()->first();
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
