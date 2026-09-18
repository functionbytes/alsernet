<?php

namespace Modules\Supplier\Models\Product;

use App\Models\User;
use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supplier\Database\Factories\Product\ProductChatFactory;
use Modules\Supplier\Models\Ai\AiContent;

class ProductChat extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_product_chats';

    protected $fillable = [
        'uid',
        'product_id',
        'user_id',
        'title',
        'model',
        'web_search_enabled',
        'prompt_uid',
        'total_cost',
        'total_tokens',
        'messages_count',
        'saved_content_id',
    ];

    protected $attributes = [
        'total_cost' => 0,
        'total_tokens' => 0,
        'messages_count' => 0,
        'web_search_enabled' => false,
    ];

    protected function casts(): array
    {
        return [
            'web_search_enabled' => 'boolean',
            'total_cost' => 'decimal:6',
            'total_tokens' => 'integer',
            'messages_count' => 'integer',
        ];
    }

    protected static function newFactory(): ProductChatFactory
    {
        return ProductChatFactory::new();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function savedContent(): BelongsTo
    {
        return $this->belongsTo(AiContent::class, 'saved_content_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ProductChatMessage::class, 'chat_id')->orderBy('created_at');
    }

    public function conversation(): HasMany
    {
        return $this->hasMany(ProductChatMessage::class, 'chat_id')
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at');
    }

    public function lastAssistantMessage(): ?ProductChatMessage
    {
        return $this->messages()
            ->where('role', 'assistant')
            ->orderByDesc('created_at')
            ->first();
    }
}
