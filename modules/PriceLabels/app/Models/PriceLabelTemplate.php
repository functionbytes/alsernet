<?php

namespace Modules\PriceLabels\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
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

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(function () {
            $path = $this->image_vertical ?: $this->image_horizontal;

            return $path ? Storage::disk('public')->url($path) : null;
        });
    }

    protected function gridSummary(): Attribute
    {
        return Attribute::get(function () {
            $parts = [];

            if (in_array($this->orientation, ['vertical', 'both'], true)) {
                $parts[] = "Vertical {$this->vertical_rows}x{$this->vertical_columns}";
            }

            if (in_array($this->orientation, ['horizontal', 'both'], true)) {
                $parts[] = "Horizontal {$this->horizontal_rows}x{$this->horizontal_columns}";
            }

            return implode(' · ', $parts);
        });
    }

    protected function fieldLabelsMap(): Attribute
    {
        return Attribute::get(function () {
            $definitions = $this->field_definitions ?: [
                ['key' => 'referencia', 'label' => 'Referencia'],
                ['key' => 'descripcion', 'label' => 'Descripcion'],
                ['key' => 'pvprp', 'label' => 'PVP recomendado proveedor'],
                ['key' => 'pvp', 'label' => 'PVP'],
            ];

            $labels = collect($definitions)->pluck('label', 'key')->all();
            $labels['label'] = 'Texto fijo (label)';

            return $labels;
        });
    }
}
