<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'return_status_history';

    protected $fillable = [
        'return_id',
        'previous_status',
        'new_status',
        'changed_by',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime'
    ];

    public function return()
    {
        return $this->belongsTo('App\Models\Return\ReturnRequest');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'changed_by');
    }
}

