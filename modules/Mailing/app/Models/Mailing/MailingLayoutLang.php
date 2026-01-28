<?php

namespace Modules\Mailing\Models\Mailing;

use Modules\Mailer\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailerLayoutLang extends Model
{
    use HasUid;

    protected $table = 'mailing_layout_langs';

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
        return $this->belongsTo(MailingLayout::class, 'layout_id', 'id');
    }

    /**
     * Relación con Lang
     */
    public function lang(): BelongsTo
    {
        return $this->belongsTo(MailingLang::class, 'lang_id', 'id');
    }
}
