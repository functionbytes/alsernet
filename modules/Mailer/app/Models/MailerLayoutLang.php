<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Mailer\Traits\HasUid;

class MailerLayoutLang extends Model
{
    use HasUid;

    protected $table = 'mailer_layout_langs';

    protected $fillable = [
        'uid',
        'layout_id',
        'lang_id',
        'subject',
        'content',
    ];

    /**
     * Relación con EmailLayout
     */
    public function layout(): BelongsTo
    {
        return $this->belongsTo(MailerLayout::class, 'layout_id', 'id');
    }

    /**
     * Relación con Lang
     */
    public function lang(): BelongsTo
    {
        return $this->belongsTo(MailerLang::class, 'lang_id', 'id');
    }
}
