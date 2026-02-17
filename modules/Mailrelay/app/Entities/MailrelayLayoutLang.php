<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailrelayLayoutLang extends Model
{
    protected $table = 'mailrelay_layout_langs';

    protected $fillable = [
        'mailrelay_layout_id',
        'lang_id',
        'name',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'mailrelay_layout_id' => 'integer',
            'lang_id' => 'integer',
        ];
    }

    /**
     * Get the layout that owns this translation.
     */
    public function layout(): BelongsTo
    {
        return $this->belongsTo(MailrelayLayout::class, 'mailrelay_layout_id');
    }

    /**
     * Get the language for this translation.
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Language::class, 'lang_id');
    }
}
