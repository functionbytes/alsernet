<?php

namespace Modules\Page\Models;

use Illuminate\Database\Eloquent\Model;

class PageCacheConfig extends Model
{
    protected $table = 'page_cache_configs';

    protected $fillable = [
        'page_id',
        'cache_enabled',
        'warm_on_save',
        'excluded_roles',
    ];

    protected $casts = [
        'cache_enabled' => 'boolean',
        'warm_on_save' => 'boolean',
        'excluded_roles' => 'array',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
