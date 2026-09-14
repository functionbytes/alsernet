<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupReduction extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_group_reduction';

    protected $primaryKey = 'id_group_reduction';

    public $timestamps = false;

    protected $fillable = [
        'id_group',
        'id_category',
        'reduction',
    ];

    protected $casts = [
        'id_group' => 'integer',
        'id_category' => 'integer',
        'reduction' => 'float',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'id_group');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_category');
    }
}
