<?php

namespace App\Models\Prestashop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{

   // use HasFactory, SoftDeletes, LogsActivity;

    protected $connection = 'prestashop';
    protected $table = "aalv_customer";

    protected $fillable = [
        'created_at',
        'updated_at'
    ];


}

