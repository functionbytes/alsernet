<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mail extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_mail';

    protected $primaryKey = 'id_mail';

    public $timestamps = false;

    protected $fillable = [
        'id_mail',
        'recipient',
        'template',
        'subject',
        'id_lang',
        'date_add',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'id_mail' => 'integer',
        'id_lang' => 'integer',
    ];

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'id_lang');
    }
}
