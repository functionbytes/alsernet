<?php

namespace App\Models\Subscriber;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubscriberLog extends Model
{
    use HasFactory , LogsActivity;

    protected $table = "subscriber_logs";

    protected $fillable = [
        'log_name',
        'description',
        'properties',
        'user_properties',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'properties' => 'array', // Para almacenar datos en JSON
        'user_properties' => 'array', // Para almacenar datos en JSON
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo('App\Models\Subscriber\SubscriberCondition','condition_id','id');
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'causer_id');
    }

    public function getActivitylogOptions(): LogOptions
    {

        return LogOptions::defaults()->logOnlyDirty()->logFillable()->setDescriptionForEvent(fn(string $eventName) => "This model has been {$eventName}");
    }

}
