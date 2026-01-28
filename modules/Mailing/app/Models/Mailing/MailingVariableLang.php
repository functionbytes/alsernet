<?php

namespace Modules\Mailing\Models\Mailing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MailerVariableLang extends Model
{
    protected $table = 'mailing_variable_langs';

    protected $fillable = [
        'uid',
        'mailing_variable_id',
        'lang_id',
        'name',
        'description',
        'value',
    ];

    /**
     * Get the mail variable this translation belongs to
     */
    public function mailVariable(): BelongsTo
    {
        return $this->belongsTo(MailerVariable::class, 'mailing_variable_id');
    }

    /**
     * Get the language this translation belongs to
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Lang::class, 'lang_id');
    }

    /**
     * Boot method to generate UID on creation
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->uid) {
                $model->uid = (string) Str::uuid();
            }
        });
    }
}
