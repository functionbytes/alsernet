<?php

namespace Modules\Mailing\Models\Mailing;

use Modules\Mailer\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uid
 * @property int $mailing_template_id
 * @property int $lang_id
 * @property string|null $subject
 * @property string|null $preheader
 * @property string|null $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Modules\Mailer\Models\MailingTemplate|null $emailTemplate
 * @property-read \App\Models\Lang|null $lang
 */
class MailerTemplateLang extends Model
{
    use HasUid;

    protected $table = 'mailing_template_langs';

    protected $fillable = [
        'uid',
        'mailing_template_id',
        'lang_id',
        'subject',
        'preheader',
        'content',
    ];

    /**
     * Relación con EmailTemplate
     */
    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(MailingTemplate::class, 'mailing_template_id', 'id');
    }

    /**
     * Relación con Lang
     */
    public function lang(): BelongsTo
    {
        return $this->belongsTo(MailingLang::class, 'lang_id', 'id');
    }
}
