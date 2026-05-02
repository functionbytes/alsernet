<?php

namespace Modules\Remarketing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'remarketing_customers';

    protected $fillable = [
        'store_id',
        'email',
        'email_hash',
        'first_name',
        'last_name',
        'phone',
        'country',
        'locale',
        'external_id',
        'tags',
        'attributes',
        'consent_marketing',
        'consent_confirmed_at',
        'double_optin_token',
        'double_optin_sent_at',
        'status',
        'unsubscribed_at',
        'rfm_recency',
        'rfm_frequency',
        'rfm_monetary',
        'clv_historical',
        'orders_count',
        'last_order_at',
        'birthday',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'attributes' => 'array',
            'consent_marketing' => 'boolean',
            'consent_confirmed_at' => 'datetime',
            'double_optin_sent_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'clv_historical' => 'decimal:2',
            'last_order_at' => 'datetime',
            'birthday' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function consentEvents(): HasMany
    {
        return $this->hasMany(ConsentEvent::class);
    }

    public function automationRuns(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }

    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->where('status', 'subscribed');
    }

    public function scopeWithEmailMarketingConsent(Builder $query): Builder
    {
        return $query->where('consent_marketing', 1)->where('status', 'subscribed');
    }
}
