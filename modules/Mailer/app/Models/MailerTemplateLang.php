<?php

namespace Modules\Mailer\Models;

use Modules\Mailer\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uid
 * @property int $mailer_template_id
 * @property int $lang_id
 * @property string|null $subject
 * @property string|null $preheader
 * @property string|null $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Modules\Mailer\Models\MailerTemplate|null $emailTemplate
 * @property-read \App\Models\Lang|null $lang
 */
class MailerTemplateLang extends Model
{
    use HasUid;

    protected $table = 'mailer_template_langs';

    protected $fillable = [
        'uid',
        'mailer_template_id',
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
        return $this->belongsTo(MailerTemplate::class, 'mailer_template_id', 'id');
    }

    /**
     * Relación con Lang
     */
    public function lang(): BelongsTo
    {
        return $this->belongsTo(MailerLang::class, 'lang_id', 'id');
    }
}
