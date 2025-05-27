<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnRequest extends Model
{
    protected $table = 'return_requests';
    protected $primaryKey = 'id_return_request';

    protected $fillable = [
        'return_address', 'pickup_selection', 'id_order', 'id_customer', 'id_address',
        'id_order_detail', 'id_return_status', 'id_return_type', 'description', 'id_return_reason',
        'product_quantity', 'product_quantity_reinjected', 'received_date', 'pickup_date',
        'is_refunded', 'is_wallet_used', 'id_shop', 'customer_name', 'email', 'phone',
        'iban', 'pdf_path', 'logistics_mode', 'created_by'
    ];

    protected $casts = [
        'received_date' => 'datetime',
        'pickup_date' => 'datetime',
        'is_refunded' => 'boolean',
    ];

    // Relaciones usando namespace completo
    public function status(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnStatus', 'id_return_status', 'id_return_status');
    }

    public function returnType(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnType', 'id_return_type', 'id_return_type');
    }

    public function returnReason(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnReason', 'id_return_reason', 'id_return_reason');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnDiscussion', 'id_return_request', 'id_return_request');
    }

    public function history(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnHistory', 'id_return_request', 'id_return_request');
    }

    public function payments(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnPayment', 'id_return_request', 'id_return_request');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnAttachment', 'id_return_request', 'id_return_request');
    }

    // Scopes
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('id_customer', $customerId);
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    public function scopeByStatus($query, $statusId)
    {
        return $query->where('id_return_status', $statusId);
    }

    public function scopeByOrder($query, $orderId)
    {
        return $query->where('id_order', $orderId);
    }

    public function scopeByLogisticsMode($query, $mode)
    {
        return $query->where('logistics_mode', $mode);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('status', function($q) {
            $q->where('active', true);
        });
    }

    public function scopePending($query)
    {
        return $query->whereHas('status.state', function($q) {
            $q->where('name', 'New');
        });
    }

    public function scopeApproved($query)
    {
        return $query->whereHas('status.state', function($q) {
            $q->where('name', 'Verification');
        });
    }

    public function scopeCompleted($query)
    {
        return $query->whereHas('status.state', function($q) {
            $q->where('name', 'Close');
        });
    }

    public function scopeRefunded($query)
    {
        return $query->where('is_refunded', true);
    }

    // Métodos auxiliares
    public function getStatusName($langId = 1, $shopId = 1)
    {
        $translation = $this->status->getTranslation($langId, $shopId);
        return $translation ? $translation->name : $this->status->state->name;
    }

    public function getReturnTypeName($langId = 1, $shopId = 1)
    {
        $translation = $this->returnType->getTranslation($langId, $shopId);
        return $translation ? $translation->name : 'Desconocido';
    }

    public function getReturnReasonName($langId = 1, $shopId = 1)
    {
        $translation = $this->returnReason->getTranslation($langId, $shopId);
        return $translation ? $translation->name : 'Desconocido';
    }

    public function isWithinReturnPeriod()
    {
        $returnDaysLimit = config('returns.return_days_limit', 30);
        return $this->created_at->diffInDays(now()) <= $returnDaysLimit;
    }

    public function isApproved()
    {
        return $this->history()
            ->where('id_return_status', config('returns.approved_status_id', 2))
            ->exists();
    }

    public function isPending()
    {
        return $this->status->state->name === 'New';
    }

    public function isCompleted()
    {
        return $this->status->state->name === 'Close';
    }

    public function canBeModified()
    {
        return in_array($this->status->state->name, ['New', 'Verification']);
    }

    public function getLogisticsModeLabel()
    {
        $modes = [
            'customer_transport' => 'Agencia de transporte (cuenta del cliente)',
            'home_pickup' => 'Recogida a domicilio',
            'store_delivery' => 'Entrega en tienda',
            'inpost' => 'InPost'
        ];

        return $modes[$this->logistics_mode] ?? 'No especificado';
    }
}
