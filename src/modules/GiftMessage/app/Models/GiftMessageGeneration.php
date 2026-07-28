<?php

namespace Modules\GiftMessage\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GiftMessage\Database\Factories\GiftMessageGenerationFactory;

class GiftMessageGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
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
        return GiftMessageGenerationFactory::new();
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
