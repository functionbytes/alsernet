<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Ecommerce\Database\Factories\CustomerFactory;
use Modules\Ecommerce\Enums\CustomerStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use LogsActivity;
    use Notifiable;

    protected $table = 'ecommerce_customers';

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'status',
        'private_notes',
        'email_verified_at',
        'email_verification_token',
        'provider',
        'provider_id',
        'wishlist_share_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'email',
                'status',
                'phone',
                'email_verified_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('ecommerce_customer');
    }

    protected function casts(): array
    {
        return [
            'status' => CustomerStatus::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function defaultAddress(): ?CustomerAddress
    {
        return $this->addresses()->where('is_default', true)->first();
    }

    public function recentlyViewed(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'ecommerce_customer_recently_viewed_products', 'customer_id', 'product_id')
            ->withPivot('session_id')
            ->withTimestamps()
            ->latest('ecommerce_customer_recently_viewed_products.created_at');
    }
}
