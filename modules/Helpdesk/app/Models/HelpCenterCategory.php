<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpCenterCategory extends Model
{
    use SoftDeletes;

    protected $table = 'helpdesk_helpcenter_categories';

    protected $fillable = [
        'name',
        'description',
        'image',
        'icon',
        'position',
        'parent_id',
        'is_section',
        'visible_to_role',
        'managed_by_role',
    ];

    protected $casts = [
        'is_section' => 'boolean',
        'position' => 'integer',
        'parent_id' => 'integer',
    ];

    /**
     * A category can have many child sections
     */
    public function sections(): HasMany
    {
        return $this->hasMany(HelpCenterCategory::class, 'parent_id')
            ->where('is_section', true)
            ->orderBy('position');
    }

    /**
     * A section belongs to a parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(HelpCenterCategory::class, 'parent_id');
    }

    /**
     * Articles relationship (many-to-many with position)
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(HelpCenterArticle::class, 'helpdesk_helpcenter_category_article', 'category_id', 'article_id')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('helpdesk_helpcenter_category_article.position');
    }

    /**
     * Scope to get only categories (not sections)
     */
    public function scopeCategories($query)
    {
        return $query->where('is_section', false)->whereNull('parent_id');
    }

    /**
     * Scope to get only sections
     */
    public function scopeSections($query)
    {
        return $query->where('is_section', true)->whereNotNull('parent_id');
    }
}
