<?php

namespace App\Models\Product;

use App\Models\Kardex;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductLocation extends Model
{

    use HasFactory;
    protected $table = "product_locations";


    protected $fillable = [
        'uid',
        'product_id',
        'location_id',
        'shop_id',
        'kardex',
        'management',
        'count',
        'created_at',
        'updated_at'
    ];


    //protected static function boot()
    //{
        //parent::boot();

        //static::creating(function ($productLocation) {
       //     $productLocation->updateKardex();
//});

       // static::updating(function ($productLocation) {
        //    $productLocation->updateKardex();
       // });

       // static::retrieved(function ($productLocation) {
       //     $productLocation->updateKardex();
        //});
   // }

    public function updateKardex()
    {
        //dd($this->product);

        $kardex = new Kardex();
        $resultado = $kardex->searchParameters('referencia', $this->product->reference);

        if ($resultado && property_exists($resultado, 'KAR_CANTIDAD')) {
            $this->kardex = $resultado->KAR_CANTIDAD;
        }
    }


    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query, $uid)
{
        return $query->where('uid', $uid)->first();
}

    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Product\Product','product_id','id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo('App\Models\Location','location_id','id');
    }

    public function items()
    {

        return $this->belongsTo('App\Models\Inventarie\InventarieLocationItem','location_id','id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo('App\Models\Shop','shop_id','id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany('App\Models\Order\Order');
    }

}
