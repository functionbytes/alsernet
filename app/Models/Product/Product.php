<?php

namespace App\Models\Product;

use App\Models\Kardex;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    use HasFactory;

    protected $table = "products";

    protected $fillable = [
        'uid',
        'title',
        'slug',
        'reference',
        'barcode',
        'stock',
        'available',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeBarcode($query, $barcode)
    {
        return $query->where('barcode',$barcode)->first();
    }

    public function scopeBarcodeExits($query, $barcode)
    {
        return $query->where('barcode',$barcode)  ->exists();
    }

    public function scopeUid($query, $uid)
    {
            return $query->where('uid', $uid)->first();
    }

    public function scopeSlug($query ,$slug)
    {
        return $query->where('slug', $slug)->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    public function localizations()
    {
        return $this->hasMany('App\Models\Product\ProductLocation');
    }

    public function locations()
    {
        return $this->hasMany('App\Models\Product\ProductLocation');
    }

    public function count()
    {
        return $this->hasMany('App\Models\Product\ProductLocation')->sum('count');
    }

    public function items()
    {
        return $this->hasMany('App\Models\Warehouse\InventarieLocationItem');
    }

    public function scopeKardex($query, $product)

    {
        $kardex = new Kardex();
        $response = $kardex->searchParameters('refencia', $product->reference);

        if ($response!=null && isset($response[0]->KAR_CANTIDAD)) {
            $product->kardex = $response[0]->KAR_CANTIDAD;
            $product->save();
        }
    }

    /**
     * Validar que el código de barras sea válido
     *
     * @param string $barcode
     * @return bool
     */
    public function isValidBarcode(string $barcode): bool
    {
        // Validar que sea numérico y tenga longitud razonable
        if (!is_numeric($barcode)) {
            return false;
        }

        $length = strlen($barcode);
        return $length >= 8 && $length <= 13;
    }

    /**
     * Obtener el total de stock del producto en todas las ubicaciones
     *
     * @return int
     */
    public function getTotalStock(): int
    {
        return $this->locations()->sum('count') ?? 0;
    }

    /**
     * Obtener el stock por ubicación
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stock()
    {
        return $this->hasMany('App\Models\Product\ProductLocation');
    }

    /**
     * Buscar productos por criterio (barcode, referencia o título)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByCriteria($query, string $search)
    {
        return $query->where('barcode', $search)
            ->orWhere('reference', $search)
            ->orWhere('title', 'like', "%$search%");
    }

}
