<?php

namespace Modules\Page\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PageCacheAudit extends Model
{
    protected $table = 'page_cache_audits';

    protected $fillable = [
        'action',
        'page_id',
        'slug',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
