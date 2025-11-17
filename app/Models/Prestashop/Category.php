<?php

namespace App\Models\Prestashop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_category';
    protected $primaryKey = 'id_category';
    public $timestamps = false;

    protected $fillable = [
        'id_category', 'id_parent', 'id_shop_default', 'level_depth', 'nleft', 'nright', 'active', 'date_add', 'date_upd', 'position', 'is_root_category'
    ];

    public function langs():HasMany
    {
        return $this->hasMany('App\Models\Prestashop\CategoryLang', 'id_category', 'id_category')->select('name');
    }

}
