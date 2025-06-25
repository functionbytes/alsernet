<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnStatus extends Model
{
    protected $table = 'return_status';
    protected $primaryKey = 'id';

    protected $fillable = [
        'state_id', 'color', 'send_email', 'is_pickup', 'is_received',
        'is_refunded', 'shown_to_customer', 'active'
    ];

    protected $casts = [
        'send_email' => 'boolean',
        'is_pickup' => 'boolean',
        'is_received' => 'boolean',
        'is_refunded' => 'boolean',
        'shown_to_customer' => 'boolean',
        'active' => 'boolean',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnState', 'state_id', 'state_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnStatusLang', 'id_return_status', 'id_return_status');
    }

    public function requests(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnRequest', 'id_return_status', 'id_return_status');
    }

    public function getTranslation($langId = 1, $shopId = 1)
    {
        return $this->translations()->where('id_lang', $langId)->where('id_shop', $shopId)->first();
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeVisibleToCustomer($query)
    {
        return $query->where('shown_to_customer', true);
    }
}
