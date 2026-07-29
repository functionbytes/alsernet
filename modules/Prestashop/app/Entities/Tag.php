<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tag extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_tag';

    protected $primaryKey = 'id_tag';

    public $timestamps = false;

    protected $fillable = [
        'id_lang',
        'name',
    ];

    protected $casts = [
        'id_lang' => 'integer',
    ];

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'id_lang');
    }
}
