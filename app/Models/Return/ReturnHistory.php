<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnHistory extends Model
{
    protected $table = 'return_history';
    protected $primaryKey = 'id_return_history';

    protected $fillable = [
        'id_return_request', 'id_return_status', 'description', 'id_employee',
        'set_pickup', 'is_refunded', 'shown_to_customer'
    ];

    protected $casts = [
        'set_pickup' => 'boolean',
        'is_refunded' => 'boolean',
        'shown_to_customer' => 'boolean',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnRequest', 'request_id', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnStatus', 'status_id', 'id');
    }

    public function scopeVisibleToCustomer($query)
    {
        return $query->where('shown_to_customer', true);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('id_employee', $employeeId);
    }
}
