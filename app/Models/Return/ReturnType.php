<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnType extends Model
{
    protected $table = 'return_types';
    protected $primaryKey = 'id_return_type';

    protected $fillable = [];

    public function translations(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnTypeLang', 'id_return_type', 'id_return_type');
    }

    public function requests(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnRequest', 'id_return_type', 'id_return_type');
    }

    public function getTranslation($langId = 1, $shopId = 1)
    {
        return $this->translations()
            ->where('id_lang', $langId)
            ->where('id_shop', $shopId)
            ->first();
    }

    // Constantes para tipos de devolución
    const TYPE_REFUND = 1;      // Reembolso
    const TYPE_REPLACEMENT = 2;  // Reemplazo
    const TYPE_REPAIR = 3;       // Reparación
}
