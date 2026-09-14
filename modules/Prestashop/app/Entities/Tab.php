<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tab extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_tab';

    protected $primaryKey = 'id_tab';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'class_name',
        'route_name',
        'module',
        'id_parent',
        'position',
        'icon',
        'wording',
        'wording_domain',
    ];

    protected $casts = [
        'id_parent' => 'integer',
        'position' => 'integer',
    ];

    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_parent');
    }
}
