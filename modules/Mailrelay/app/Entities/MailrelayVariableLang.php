<?php

namespace Modules\Mailrelay\Entities;

use App\Models\Language;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailrelayVariableLang extends Model
{
    protected $table = 'mailrelay_variable_langs';

    protected $fillable = [
        'mailrelay_variable_id',
        'lang_id',
        'name',
        'description',
        'example_value',
    ];

    protected function casts(): array
    {
        return [
            'mailrelay_variable_id' => 'integer',
            'lang_id' => 'integer',
        ];
    }

    /**
     * Get the variable that owns this translation.
     */
    public function variable(): BelongsTo
    {
        return $this->belongsTo(MailrelayVariable::class, 'mailrelay_variable_id');
    }

    /**
     * Get the language for this translation.
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'lang_id');
    }
}
