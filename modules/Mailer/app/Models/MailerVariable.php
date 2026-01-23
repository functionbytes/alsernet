<?php

namespace Modules\Mailer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MailerVariable extends Model
{
    protected $table = 'mailer_variables';

    protected $fillable = [
        'uid',
        'key',
        'name',
        'description',
        'example_value',
        'category',
        'module',
        'is_system',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * Get translations for this variable
     */
    public function translations(): HasMany
    {
        return $this->hasMany(MailerVariableLang::class, 'mailer_variable_id');
    }

    /**
     * Get translation for a specific language
     */
    public function translate(int $langId): ?MailerVariableLang
    {
        return $this->translations()
            ->where('lang_id', $langId)
            ->first();
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
