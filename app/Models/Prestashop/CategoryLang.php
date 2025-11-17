<?php

namespace App\Models\Prestashop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CategoryLang extends Model
{
    protected $connection = 'prestashop';
    protected $table = 'aalv_category_lang';
    protected $primaryKey = 'id_category';
    public $timestamps = false;

    protected $fillable = [
        'id_category','id_shop',  'id_lang', 'name'
    ];


    public function lang():HasOne
    {
        return $this->hasOne('App\Models\Prestashop\Lang', 'id_lang', 'id_lang');
    }

    public function procesar(){
        return $this->lang();
    }


}
