<?php

namespace App\Models\Subscriber;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class SubscriberLog extends Model
{
    use  LogsActivity;

    protected $table = "subscriber_logs";

    protected $fillable = [
        'log_name',
        'description',
        'observation',
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
        'properties' => 'array',
        'user_properties' => 'array',
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
