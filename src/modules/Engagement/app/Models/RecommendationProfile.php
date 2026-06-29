<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Engagement\Database\Factories\RecommendationProfileFactory;
use Modules\Helpdesk\Models\Customer;

class RecommendationProfile extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'engagement_recommendation_profiles';

    protected $fillable = [
        'customer_id',
        'viewed_products',
        'categories',
        'cart_history',
        'last_purchased_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_products' => 'array',
            'categories' => 'array',
            'cart_history' => 'array',
            'last_purchased_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function recordProductView(array $product): void
    {
        $viewed = $this->viewed_products ?? [];
        $id = $product['id'] ?? null;

        if ($id === null) {
            return;
        }

        $found = false;
        foreach ($viewed as $idx => $entry) {
            if (($entry['id'] ?? null) === $id) {
                $viewed[$idx]['count'] = ($entry['count'] ?? 0) + 1;
                $viewed[$idx]['viewed_at'] = now()->toIso8601String();
                $found = true;
                break;
            }
        }

        if (! $found) {
            $viewed[] = [
                'id' => $id,
                'name' => $product['name'] ?? null,
                'category' => $product['category'] ?? null,
                'count' => 1,
                'viewed_at' => now()->toIso8601String(),
            ];
        }

        $this->viewed_products = $viewed;

        if (isset($product['category'])) {
            $cats = $this->categories ?? [];
            $cats[$product['category']] = ($cats[$product['category']] ?? 0) + 1;
            $this->categories = $cats;
        }

        $this->save();
    }

    protected static function newFactory(): RecommendationProfileFactory
    {
        return RecommendationProfileFactory::new();
    }
}
