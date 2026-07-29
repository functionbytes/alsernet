<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSession extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_employee_session';

    protected $primaryKey = 'id_employee_session';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_employee',
        'token',
    ];

    protected $casts = [
        'id_employee' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'id_employee');
    }
}
