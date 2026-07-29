<?php

namespace Modules\Supplier\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Product\ProductChatMessageFactory;

class ProductChatMessage extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'supplier_product_chat_messages';

    protected $fillable = [
        'chat_id',
        'role',
        'content',
        'sources',
        'web_search_used',
        'input_tokens',
        'output_tokens',
        'cost',
        'model',
        'latency_ms',
    ];

    protected function casts(): array
    {
        return [
            'sources' => 'array',
            'web_search_used' => 'boolean',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cost' => 'decimal:6',
            'latency_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ProductChatMessageFactory
    {
        return ProductChatMessageFactory::new();
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(ProductChat::class, 'chat_id');
    }

    public function getTotalTokensAttribute(): int
    {
        return (int) ($this->input_tokens + $this->output_tokens);
    }
}
