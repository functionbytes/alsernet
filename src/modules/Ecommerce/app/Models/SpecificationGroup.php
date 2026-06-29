<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpecificationGroup extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_specification_groups';

    protected $fillable = [
        'name',
        'description',
    ];

    public function attributes(): HasMany
    {
        return $this->hasMany(SpecificationAttribute::class, 'group_id');
    }

    public function tables(): BelongsToMany
    {
        return $this->belongsToMany(SpecificationTable::class, 'ecommerce_specification_table_group', 'group_id', 'table_id');
    }
}
