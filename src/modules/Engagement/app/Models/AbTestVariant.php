<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Engagement\Database\Factories\AbTestVariantFactory;

class AbTestVariant extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'engagement_ab_test_variants';

    protected $fillable = [
        'ab_test_id',
        'name',
        'config',
        'weight',
        'impressions',
        'conversions',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'weight' => 'integer',
            'impressions' => 'integer',
            'conversions' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function abTest(): BelongsTo
    {
        return $this->belongsTo(AbTest::class, 'ab_test_id');
    }

    public function conversionRate(): float
    {
        if ($this->impressions === 0) {
            return 0.0;
        }

        return round(($this->conversions / $this->impressions) * 100, 2);
    }

    protected static function newFactory(): AbTestVariantFactory
    {
        return AbTestVariantFactory::new();
    }
}
