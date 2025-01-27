<?php

namespace App\Models\Faq;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Faq extends Model
{
    use HasFactory  , LogsActivity;

    protected $table = "faqs";

    protected static $recordEvents = ['deleted','updated','created'];

    protected $fillable = [
        'slack',
        'title',
        'slug',
        'description',
        'available',
        'category_id',
        'created_at',
        'updated_at'
    ];

    public function getActivitylogOptions(): LogOptions
    {

        return LogOptions::defaults()
            ->logOnlyDirty() 
            ->logFillable() 
            ->setDescriptionForEvent(fn(string $eventName) => "This model has been {$eventName}");

    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }
    public function scopeId($query ,$id)
    {
        return $query->where('id' ,$id)->first();
    }
    
    public function scopeSlack($query ,$slack)
    {
        return $query->where('slack', $slack)->first();
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo('App\Models\Faq\FaqCategorie','category_id','id');
    }
    
}
