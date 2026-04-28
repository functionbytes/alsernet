<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $fillable = ['key', 'endpoint', 'response', 'status_code', 'expires_at'];

    protected $casts = [
        'response' => 'array',
        'expires_at' => 'datetime',
    ];
}
