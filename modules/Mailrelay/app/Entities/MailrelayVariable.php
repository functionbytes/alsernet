<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailrelayVariable extends Model
{
    protected $table = 'mailrelay_variables';

    protected $fillable = [
        'name',
        'variable_key',
        'category',
        'module',
        'description',
        'example_value',
        'status',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the translations for this variable.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(MailrelayVariableLang::class, 'mailrelay_variable_id');
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
     * Get the placeholder format for this variable.
     */
    public function getPlaceholder(): string
    {
        return '{'.$this->variable_key.'}';
    }

    /**
     * Get the replacement value for this variable based on context.
     *
     * @return mixed
     */
    public function getReplacementValue(array $context)
    {
        // This will be implemented in VariableValueService
        // For now, return example value or empty string
        return $this->example_value ?? '';
    }

    /**
     * Get variables by category.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByCategory(string $category)
    {
        return static::where('category', $category)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get variables by module.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByModule(string $module)
    {
        return static::where('module', $module)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get all active variables.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllActive()
    {
        return static::where('status', 'active')
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Scope to filter only active variables.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter only system variables.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to filter only custom (non-system) variables.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope to filter by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by module.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
