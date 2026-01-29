<?php

namespace Modules\Mailing\Models;

use Modules\Mailing\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasFactory;
    use HasUid;

    protected $fillable = ['attribute_id', 'product_id', 'value'];
}
