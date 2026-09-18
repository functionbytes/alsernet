<?php

namespace Modules\PriceLabels\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceLabelFont extends Model
{
    use HasFactory;

    protected $table = 'price_label_fonts';

    protected $fillable = [
        'name',
        'family',
        'weight',
        'style',
        'file_path',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Etiqueta legible de la variante: "Negrita", "Cursiva", "Negrita cursiva" o "Regular".
     */
    public function variantLabel(): string
    {
        $parts = [];

        if ($this->weight === 'bold') {
            $parts[] = 'Negrita';
        }

        if ($this->style === 'italic') {
            $parts[] = $parts === [] ? 'Cursiva' : 'cursiva';
        }

        return $parts === [] ? 'Regular' : implode(' ', $parts);
    }
}
