<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewTranslation extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'product_review_translations';

    protected $guarded = ['id'];

    protected $casts = [
        'ps_lang_id' => 'integer',
        'chars' => 'integer',
        'reviewed' => 'boolean',
        'inherited' => 'boolean',
        'ps_comment_id' => 'integer',
        'published_at' => 'datetime',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Estado de cara a la ficha.
     *
     * "Heredada" es la que ya estaba en la tienda antes de este módulo: se ve
     * publicada, pero nadie la ha revisado y viene del proceso que traducía
     * unas traducciones de otras.
     */
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
