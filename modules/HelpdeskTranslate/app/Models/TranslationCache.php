<?php

namespace Modules\HelpdeskTranslate\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationCache extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_translation_cache';

    protected $fillable = [
        'text_hash',
        'text_source',
        'text_translated',
        'source_lang',
        'target_lang',
        'provider',
        'hits',
        'chars_saved',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'hits' => 'integer',
            'chars_saved' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    public static function makeHash(string $text, string $source, string $target, string $provider): string
    {
        return hash('sha256', $provider.'|'.strtolower($source).'|'.strtolower($target).'|'.$text);
    }
}
