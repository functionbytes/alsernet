<?php

namespace Modules\Supplier\Models\Ai;

use Illuminate\Database\Eloquent\Model;

class AiContentStatus extends Model
{
    protected $table = 'supplier_ai_content_statuses';

    protected $fillable = [
        'key',
        'label',
        'badge_class',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public static function getByKey(string $key): ?self
    {
        return self::where('key', $key)->first();
    }
}
