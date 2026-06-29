<?php

namespace Modules\Remarketing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentEvent extends Model
{
    use HasFactory;

    protected $table = 'remarketing_consent_events';

    const UPDATED_AT = null;

    protected $fillable = [
        'store_id',
        'customer_id',
        'email',
        'event_type',
        'source',
        'ip',
        'user_agent',
        'form_url',
        'policy_version',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
