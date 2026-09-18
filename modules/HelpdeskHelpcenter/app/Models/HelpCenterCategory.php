<?php

namespace Modules\HelpdeskHelpcenter\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpdeskHelpcenter\Database\Factories\HelpCenterCategoryFactory;

class HelpCenterCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): HelpCenterCategoryFactory
    {
        return HelpCenterCategoryFactory::new();
    }

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_helpcenter_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'icon',
        'position',
        'parent_id',
        'is_section',
        'visible_to_role',
        'managed_by_role',
    ];

    protected function casts(): array
    {
        return [
            'is_section' => 'boolean',
            'position' => 'integer',
            'parent_id' => 'integer',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(HelpCenterCategory::class, 'parent_id')
            ->where('is_section', true)
            ->orderBy('position');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(HelpCenterCategory::class, 'parent_id');
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(HelpCenterArticle::class, 'helpdesk_helpcenter_category_article', 'category_id', 'article_id')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('helpdesk_helpcenter_category_article.position');
    }

    public function scopeCategories(Builder $query): Builder
    {
        return $query->where('is_section', false)->whereNull('parent_id');
    }

    public function scopeSections(Builder $query): Builder
    {
        return $query->where('is_section', true)->whereNotNull('parent_id');
    }
}
