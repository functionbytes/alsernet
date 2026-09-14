<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_guest';

    protected $primaryKey = 'id_guest';

    public $timestamps = false;

    protected $fillable = [
        'id_operating_system',
        'id_web_browser',
        'id_customer',
        'javascript',
        'screen_resolution_x',
        'screen_resolution_y',
        'screen_color',
        'sun_java',
        'adobe_flash',
        'adobe_director',
        'apple_quicktime',
        'real_player',
        'windows_media',
        'accept_language',
        'mobile_theme',
    ];

    protected $casts = [
        'mobile_theme' => 'boolean',
        'id_operating_system' => 'integer',
        'id_web_browser' => 'integer',
        'id_customer' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function carts(): HasMany
    {
        return $this->hasMany('App\Models\Prestashop\Cart\Cart', 'id_guest', 'id_guest');
    }
}
