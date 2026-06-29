<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlobalOption extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_global_options';

    protected $fillable = [
        'name',
        'option_type',
        'required',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $model) {
            $model->values()->delete();
        });
    }

    public function values(): HasMany
    {
        return $this->hasMany(GlobalOptionValue::class, 'option_id')->orderBy('order');
    }
}
