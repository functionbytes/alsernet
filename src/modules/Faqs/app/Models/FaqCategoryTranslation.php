<?php

namespace Modules\Faqs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaqCategoryTranslation extends Model
{
    protected $table = 'faq_category_translations';

    protected $fillable = ['faq_category_id', 'locale', 'name', 'description'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }
}
