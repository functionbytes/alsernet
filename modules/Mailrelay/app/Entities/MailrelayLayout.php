<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailrelayLayout extends Model
{
    protected $table = 'mailrelay_layouts';

    protected $fillable = [
        'name',
        'type',
        'content',
        'description',
        'status',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the translations for this layout.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(MailrelayLayoutLang::class, 'mailrelay_layout_id');
    }

    /**
     * Get the translation for the current locale.
     */
    public function translation(?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return $this->translations()
            ->whereHas('language', function ($query) use ($locale) {
                $query->where('code', $locale);
            })
            ->first();
    }

    /**
     * Get templates that use this layout.
     */
    public function templates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class, 'layout_id');
    }

    /**
     * Get the default layout.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)
            ->where('status', 'active')
            ->where('type', 'layout')
            ->first();
    }

    /**
     * Set this layout as the default.
     */
    public function setAsDefault(): bool
    {
        // Remove default flag from all other layouts
        static::where('id', '!=', $this->id)
            ->where('type', 'layout')
            ->update(['is_default' => false]);

        // Set this as default
        $this->is_default = true;

        return $this->save();
    }

    /**
     * Get layouts by type.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByType(string $type)
    {
        return static::where('type', $type)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Scope to filter only active layouts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get only layouts (not partials or components).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLayouts($query)
    {
        return $query->where('type', 'layout');
    }

    /**
     * Scope to get only partials.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePartials($query)
    {
        return $query->where('type', 'partial');
    }

    /**
     * Scope to get only components.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeComponents($query)
    {
        return $query->where('type', 'component');
    }

    /**
     * Check if this is a layout type.
     */
    public function isLayout(): bool
    {
        return $this->type === 'layout';
    }

    /**
     * Check if this is a partial type.
     */
    public function isPartial(): bool
    {
        return $this->type === 'partial';
    }

    /**
     * Check if this is a component type.
     */
    public function isComponent(): bool
    {
        return $this->type === 'component';
    }
}
