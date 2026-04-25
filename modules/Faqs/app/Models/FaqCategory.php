<?php

namespace Modules\Faqs\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Faqs\Enums\FaqStatus;

class FaqCategory extends Model
{
    use HasFactory;

    protected $table = 'faq_categories';

    protected $fillable = [
        'name',
        'description',
        'order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => FaqStatus::class,
            'order' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', FaqStatus::PUBLISHED);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'category_id')->orderBy('order');
    }

    public function publishedFaqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'category_id')
            ->where('status', FaqStatus::PUBLISHED)
            ->orderBy('order');
    }
}
