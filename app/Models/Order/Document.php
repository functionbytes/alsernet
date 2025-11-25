<?php

namespace App\Models\Order;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model  implements HasMedia
{
    use HasFactory ,HasUid ,  InteractsWithMedia;

    protected $table = "request_documents";

    protected $casts = [
        'reminder_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    protected $fillable = [
        'uid',
        'type',
        'proccess',
        'reference',
        'customer_id',
        'cart_id',
        'source',
        'confirmed_at',
        // Datos desnormalizados del cliente
        'customer_firstname',
        'customer_lastname',
        'customer_email',
        'customer_dni',
        'customer_company',
        // Datos desnormalizados de la orden
        'order_reference',
        'order_id',
        'cart_id',
        'order_date',
        'created_at',
        'updated_at'
    ];

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }
    public function scopeOrder($query ,$order)
    {
        return $query->where('order_id' ,$order)->first();
    }

    public function scopeId($query ,$id)
    {
        return $query->where('id' ,$id)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }

    /**
     * Filtra documentos por estado de carga (con o sin media)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $hasMedia 1 = con media, 0 = sin media, null = todos
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterByUploadStatus($query, $hasMedia = null)
    {
        if ($hasMedia === null) {
            return $query;
        }

        return $query->whereRaw("EXISTS (
            SELECT 1 FROM media
            WHERE media.model_id = request_documents.id
              AND media.model_type = ?
        ) = ?", [self::class, $hasMedia === 1 ? 1 : 0]);
    }

    /**
     * Busca documentos por nombre de cliente u orden (SIN JOINS)
     * Usa datos desnormalizados para máximo rendimiento
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search Término de búsqueda
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchByCustomerOrOrder($query, $search = '')
    {
        if (empty($search)) {
            return $query;
        }

        $search = strtolower($search);

        // Buscar en datos desnormalizados (sin join)
        return $query->where(function ($q) use ($search) {
            $q->whereRaw('LOWER(customer_firstname) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(customer_lastname) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(customer_email) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(order_reference) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('CAST(order_id AS CHAR) LIKE ?', ["%{$search}%"]);
        });
    }

    /**
     * Ordena documentos por prioridad (con media primero) y fecha
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByUploadPriority($query)
    {
        return $query
            ->orderByRaw("(SELECT COUNT(*) FROM media WHERE media.model_id = request_documents.id AND media.model_type = ?) DESC", [self::class])
            ->orderBy('request_documents.confirmed_at', 'desc')
            ->orderBy('request_documents.created_at', 'desc');
    }

    /**
     * Consulta optimizada para listar documentos en admin
     * Combina filtrado, búsqueda y ordenamiento
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search Término de búsqueda
     * @param int|null $uploadStatus 1 = con media, 0 = sin media, null = todos
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterListing($query, $search = '', $uploadStatus = null)
    {
        return $query
            ->with('customer')
            ->filterByUploadStatus($uploadStatus)
            ->searchByCustomerOrOrder($search)
            ->orderByUploadPriority();
    }

    public function getAllDocumentsUrls(): array
    {
        return $this->getMedia('documents')->map(function ($media) {
            return $media->getUrl();
        })->toArray();
    }
    public function getDocumentUrl(): ?string
    {
        $media = $this->getFirstMedia('documents');
        return $media ? $media->getUrl() : null;
    }


    public function order(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Order\Order','order_id','id_order');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Customer','customer_id','id_customer');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Cart\Cart','cart_id','id_cart');
    }

    public function products(): HasMany
    {
        return $this->hasMany(DocumentProduct::class, 'document_id');
    }

    /**
     * Captura y almacena los datos de productos del carrito
     * en la tabla document_products para evitar consultas posteriores
     */
    public function captureProducts(): void
    {
        if (!$this->cart) {
            return;
        }

        // Limpiar productos previos
        $this->products()->delete();

        $cartProducts = $this->cart->products ?? collect();

        foreach ($cartProducts as $item) {
            $this->products()->create([
                'product_id' => $item->id_product ?? $item->product->id ?? null,
                'product_name' => $item->product->name ?? $item->product_name ?? null,
                'product_reference' => $item->product->reference ?? null,
                'quantity' => (int) ($item->quantity ?? 0),
                'price' => $item->product->price ?? null,
            ]);
        }
    }

}
