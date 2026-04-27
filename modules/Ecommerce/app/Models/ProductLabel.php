<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductLabel extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_product_labels';

    protected $fillable = [
        'name',
        'color',
        'text_color',
        'status',
    ];
}
