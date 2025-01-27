<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class Hour extends Model
{
    use HasFactory , LogsActivity;

    protected $table = 'hours';

    protected static $recordEvents = ['updated'];

    protected $fillable  = [
        'weeks',
        'starttime',
        'endtime',
        'status',
        'no_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'text']);
    }

}
