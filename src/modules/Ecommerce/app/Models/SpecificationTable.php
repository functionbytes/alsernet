<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SpecificationTable extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_specification_tables';

    protected $fillable = [
        'name',
        'description',
    ];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(SpecificationGroup::class, 'ecommerce_specification_table_group', 'table_id', 'group_id')
            ->withPivot('order');
    }
}
