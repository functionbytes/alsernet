<?php

namespace Modules\Questions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionTranslation extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'product_question_translations';

    protected $guarded = ['id'];

    protected $casts = [
        'ps_lang_id' => 'integer',
        'inherited' => 'boolean',
        'reviewed' => 'boolean',
        'chars' => 'integer',
        'published_at' => 'datetime',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function getStateAttribute(): string
    {
        if ($this->reviewed && $this->published_at) {
            return 'published';
        }

        if ($this->reviewed) {
            return 'approved';
        }

        return $this->inherited ? 'inherited' : 'unreviewed';
    }
}
