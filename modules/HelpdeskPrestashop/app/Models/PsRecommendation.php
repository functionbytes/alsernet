<?php

namespace Modules\HelpdeskPrestashop\Models;

use Illuminate\Database\Eloquent\Model;

class PsRecommendation extends Model
{
    protected $table = 'helpdesk_ps_recommendations';

    protected $fillable = [
        'conversation_id',
        'product_id',
        'id_product_attribute',
        'product_name',
        'product_sku',
        'price_with_tax',
        'product_url',
        'product_image',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'price_with_tax' => 'float',
        ];
    }
}
