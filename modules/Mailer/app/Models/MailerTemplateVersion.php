<?php

namespace Modules\Mailer\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $mailer_template_id
 * @property int $lang_id
 * @property int|null $created_by
 * @property string|null $subject
 * @property string|null $content
 * @property string|null $change_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MailerTemplate $template
 * @property-read User|null $author
 */
class MailerTemplateVersion extends Model
{
    protected $table = 'mailer_template_versions';

    protected $fillable = [
        'mailer_template_id',
        'lang_id',
        'created_by',
        'subject',
        'content',
        'change_note',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(MailerTemplate::class, 'mailer_template_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo(MailerLang::class, 'lang_id');
    }
}
