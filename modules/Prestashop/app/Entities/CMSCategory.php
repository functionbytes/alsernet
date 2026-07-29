<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CMSCategory extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_cms_category';

    protected $primaryKey = 'id_cms_category';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'description',
        'id_parent',
        'position',
        'level_depth',
        'link_rewrite',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'date_add',
        'date_upd',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'id_parent' => 'integer',
        'position' => 'integer',
        'level_depth' => 'integer',
    ];

    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_parent');
    }
}
