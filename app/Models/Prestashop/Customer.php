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
    protected $primaryKey = 'id_customer';

    protected $fillable = [
        "id_customer",
        "id_shop_group",
        "id_shop",
        "id_gender",
        "id_default_group",
        "id_lang",
        "id_risk",
        "company",
        "siret",
        "ape",
        "firstname",
        "lastname",
        "email",
        "passwd",
        "last_passwd_gen",
        "birthday",
        "newsletter",
        "ip_registration_newsletter",
        "newsletter_date_add",
        "optin",
        "website",
        "outstanding_allow_amount",
        "show_public_prices",
        "max_payment_days",
        "secure_key",
        "note",
        "active",
        "is_guest",
        "deleted",
        "date_add",
        "date_upd",
        "reset_password_token",
        "reset_password_validity",

    ];

    public function addresses()
    {
        return $this->hasMany('App\Models\Prestashop\Address', 'id_customer', 'id_customer');
    }

    public function guest()
    {
        return $this->hasOne('App\Models\Prestashop\Guest', 'id_customer', 'id_customer');
    }


    public function carts()
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_customer', 'id_customer');
    }
}

