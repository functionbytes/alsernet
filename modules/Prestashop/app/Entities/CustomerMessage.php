<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMessage extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_customer_message';

    protected $primaryKey = 'id_customer_message';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_customer_thread',
        'id_employee',
        'message',
        'file_name',
        'ip_address',
        'user_agent',
        'private',
        'date_add',
        'date_upd',
        'read',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'id_customer_thread' => 'integer',
        'id_employee' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'id_employee');
    }
}
