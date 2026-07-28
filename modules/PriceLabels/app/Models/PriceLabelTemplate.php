<?php

namespace Modules\PriceLabels\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\PriceLabels\Database\Factories\PriceLabelTemplateFactory;

class PriceLabelTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return PriceLabelTemplateFactory::new();
    }

    protected $fillable = [
        'name',
        'is_active',
        'orientation',
        'image_vertical',
        'image_horizontal',
        'label_text',
        'fields',
        'positions_vertical',
        'positions_horizontal',
        'vertical_rows',
        'vertical_columns',
        'horizontal_rows',
        'horizontal_columns',
        'field_definitions',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'fields' => 'array',
            'positions_vertical' => 'array',
            'positions_horizontal' => 'array',
            'field_definitions' => 'array',
            'vertical_rows' => 'integer',
            'vertical_columns' => 'integer',
            'horizontal_rows' => 'integer',
            'horizontal_columns' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
