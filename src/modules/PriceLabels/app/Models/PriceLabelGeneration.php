<?php

namespace Modules\PriceLabels\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\PriceLabels\Database\Factories\PriceLabelGenerationFactory;

class PriceLabelGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_label_template_id',
        'template_name',
        'type',
        'rows_count',
        'file_path',
        'file_name',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'rows_count' => 'integer',
        ];
    }

    protected static function newFactory(): Factory
    {
        return PriceLabelGenerationFactory::new();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PriceLabelTemplate::class, 'price_label_template_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
