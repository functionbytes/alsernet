<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attribute extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_attribute';

    protected $primaryKey = 'id_attribute';

    public $timestamps = false;

    protected $fillable = [
        'id_attribute_group',
        'name',
        'color',
        'position',
        'default',
    ];

    protected $casts = [
        'id_attribute_group' => 'integer',
        'position' => 'integer',
    ];

    public function attributeGroup(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class, 'id_attribute_group');
    }
}
