<?php

namespace App\Models\Comparator;

use App\Models\Lang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComparatorConfiguration extends Model
{
    use HasFactory;

    protected $table = 'comparators_configurations';

    protected $fillable = [
        'comparator_id',
        'lang_id',
        'type',
        'code',
        'title',
    ];

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeId($query, $id)
    {
        return $query->where('id', $id);
    }

    public function comparator(): BelongsTo
    {
        return $this->belongsTo(Comparator::class, 'comparator_id');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Lang::class, 'lang_id');
    }
}
