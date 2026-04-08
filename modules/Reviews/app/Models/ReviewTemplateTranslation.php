<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewTemplateTranslation extends Model
{
    protected $table = 'review_template_translations';

    protected $fillable = [
        'template_id',
        'language',
        'template_text',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReviewReplyTemplate::class, 'template_id');
    }
}
