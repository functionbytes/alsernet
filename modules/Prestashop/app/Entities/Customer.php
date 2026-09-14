<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Prestashop\Entities\Shop\Shop;
use Modules\Prestashop\Entities\Shop\ShopGroup;

class Customer extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_customer';

    protected $primaryKey = 'id_customer';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_shop',
        'id_shop_group',
        'secure_key',
        'note',
        'id_default_group',
        'id_lang',
        'lastname',
        'firstname',
        'email',
        'newsletter',
        'ip_registration_newsletter',
        'newsletter_date_add',
        'optin',
        'website',
        'company',
        'siret',
        'ape',
        'id_risk',
        'passwd',
        'last_passwd_gen',
        'date_add',
        'date_upd',
        'years',
        'days',
        'months',
        'geoloc_id_country',
        'geoloc_id_state',
        'geoloc_postcode',
        'id_guest',
        'reset_password_token',
        'reset_password_validity',
    ];

    protected $casts = [
        'newsletter_date_add' => 'datetime',
        'last_passwd_gen' => 'datetime',
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'id_shop' => 'integer',
        'id_shop_group' => 'integer',
        'id_default_group' => 'integer',
        'id_lang' => 'integer',
        'id_risk' => 'integer',
        'id_guest' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'id_shop');
    }

    public function shopGroup(): BelongsTo
    {
        return $this->belongsTo(ShopGroup::class, 'id_shop_group');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'id_lang');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany('App\Models\Prestashop\Address', 'id_customer', 'id_customer');
    }

    public function guest(): HasOne
    {
        return $this->hasOne('App\Models\Prestashop\Guest', 'id_customer', 'id_customer');
    }

    public function carts(): HasMany
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_customer', 'id_customer');
    }
}
