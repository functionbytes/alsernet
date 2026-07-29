<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class AttributeGroup extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_attribute_group';

    protected $primaryKey = 'id_attribute_group';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'is_color_group',
        'position',
        'group_type',
        'public_name',
    ];

    protected $casts = [
        'is_color_group' => 'boolean',
        'position' => 'integer',
    ];
}
