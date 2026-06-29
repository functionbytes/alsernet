<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewTranslation extends Model
{
    protected $table = 'review_translations';

    protected $fillable = [
        'review_id',
        'locale_code',
        'translated_text',
        'detected_language',
        'translated_at',
    ];

    protected function casts(): array
    {
        return ['translated_at' => 'datetime'];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'review_id');
    }
}
