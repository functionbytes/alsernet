<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_employee';

    protected $primaryKey = 'id_employee';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_profile',
        'id_lang',
        'lastname',
        'firstname',
        'email',
        'passwd',
        'last_passwd_gen',
        'stats_date_from',
        'stats_date_to',
        'stats_compare_from',
        'stats_compare_to',
        'preselect_date_range',
        'bo_color',
        'default_tab',
        'bo_theme',
        'bo_width',
        'remote_addr',
        'id_last_order',
        'id_last_customer_message',
        'id_last_customer',
        'reset_password_token',
        'reset_password_validity',
    ];

    protected $casts = [
        'last_passwd_gen' => 'datetime',
        'id_profile' => 'integer',
        'id_lang' => 'integer',
        'id_last_order' => 'integer',
        'id_last_customer_message' => 'integer',
        'id_last_customer' => 'integer',
        'bo_width' => 'float',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'id_profile');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'id_lang');
    }
}
