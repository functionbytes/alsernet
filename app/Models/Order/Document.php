<?php

namespace App\Models\Order;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Document extends Model  implements HasMedia
{
    use HasFactory ,HasUid ,  InteractsWithMedia;

    protected $table = "request_documents";

    protected $casts = [
        'upload_at' => 'datetime',
        'reminder_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    protected $fillable = [
        'uid',
        'type',
        'proccess',
        'reference',
        'order_id',
        'customer_id',
        'cart_id',
        'source',
        'upload_at',
        'reminder_at',
        'confirmed_at',
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
     * Busca documentos por nombre de cliente u orden
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

        return $query
            ->join('aalv_customer', 'request_documents.customer_id', '=', 'aalv_customer.id_customer')
            ->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(aalv_customer.firstname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(aalv_customer.lastname) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('CAST(request_documents.order_id AS CHAR) LIKE ?', ["%{$search}%"]);
            })
            ->distinct()
            ->select('request_documents.*');
    }

    /**
     * Ordena documentos por prioridad (sin carga primero), fecha de creación y agrupa por día
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByUploadPriority($query)
    {
        return $query
            ->orderByRaw("CASE WHEN EXISTS (
                SELECT 1 FROM media
                WHERE media.model_id = request_documents.id
                AND media.model_type = ?
            ) THEN 1 ELSE 0 END ASC", [self::class])
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

    /**
     * Relación con los productos del documento
     */
    public function products()
    {
        return $this->hasMany(DocumentProduct::class, 'document_id');
    }

    /**
     * Detecta el tipo de documento basándose en los productos capturados
     * Valida qué etiquetas tienen los productos (DNI, ESCOPETA, RIFLE, CORTA)
     * Busca las features en los productos ya importados
     */
    public function detectDocumentType()
    {
        try {
            // Obtener los productos ya capturados del documento
            $products = $this->products()->get();

            if ($products->isEmpty()) {
                return 'general';
            }

            $documentTypes = [];

            foreach ($products as $docProduct) {

                // Obtener las features/etiquetas del producto desde Prestashop
                $features = DB::connection('prestashop')
                    ->table('aalv_feature_product')
                    ->where('id_product', $docProduct->product_id)
                    ->where('id_feature', 23) // Feature ID para tipo de venta
                    ->get();

                foreach ($features as $feature) {
                    if ($feature->id_feature_value == 263658) { // DNI
                        $documentTypes['dni'] = true;
                    } elseif ($feature->id_feature_value == 263659) { // ESCOPETA
                        $documentTypes['escopeta'] = true;
                    } elseif ($feature->id_feature_value == 263660) { // RIFLE
                        $documentTypes['rifle'] = true;
                    } elseif ($feature->id_feature_value == 263661) { // CORTA
                        $documentTypes['corta'] = true;
                    }
                }
            }

            if (!empty($documentTypes)) {
                if (isset($documentTypes['dni'])) {
                    return 'dni';
                }
                if (isset($documentTypes['escopeta'])) {
                    return 'escopeta';
                }
                if (isset($documentTypes['rifle'])) {
                    return 'rifle';
                }
                if (isset($documentTypes['corta'])) {
                    return 'corta';
                }
            }

            return 'general';

        } catch (\Exception $e) {
            Log::error('Error detectando tipo de documento: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return 'general';
        }
    }

    /**
     * Captura y sincroniza los productos de la orden Prestashop
     * Importa los productos de la tabla order_detail de Prestashop
     */
    public function captureProducts()
    {
        try {
            // Limpiar productos previos
            $this->products()->delete();

            // Obtener productos directamente de la BD de Prestashop
            $orderProducts = DB::connection('prestashop')
                ->table('aalv_order_detail')
                ->where('id_order', $this->order_id)
                ->get();

            if ($orderProducts->isEmpty()) {
                return false;
            }

            // Insertar productos en el documento
            foreach ($orderProducts as $orderProduct) {
                DocumentProduct::create([
                    'document_id' => $this->id,
                    'product_id' => $orderProduct->product_id ?? null,
                    'product_name' => $orderProduct->product_name ?? null,
                    'product_reference' => $orderProduct->product_reference ?? null,
                    'quantity' => $orderProduct->product_quantity ?? 0,
                    'price' => $orderProduct->unit_price_tax_incl ?? 0,
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Error capturando productos del documento: ' . $e->getMessage());
            return false;
        }
    }

}
