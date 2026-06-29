<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    protected $table = 'ecommerce_page_views';

    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'customer_id',
        'url',
        'referrer',
        'product_id',
        'user_agent',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }
}
