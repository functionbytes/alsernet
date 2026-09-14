<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CMS extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_cms';

    protected $primaryKey = 'id_cms';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'head_seo_title',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'content',
        'link_rewrite',
        'id_cms_category',
        'position',
        'indexation',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'id_cms_category' => 'integer',
        'position' => 'integer',
    ];

    public function cmsCategory(): BelongsTo
    {
        return $this->belongsTo(CMSCategory::class, 'id_cms_category');
    }
}
