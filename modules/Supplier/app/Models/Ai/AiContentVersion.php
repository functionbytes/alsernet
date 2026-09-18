<?php

namespace Modules\Supplier\Models\Ai;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Ai\AiContentVersionFactory;

class AiContentVersion extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'supplier_ai_content_versions';

    protected static function newFactory(): AiContentVersionFactory
    {
        return AiContentVersionFactory::new();
    }

    protected $fillable = [
        'content_id', 'user_id', 'version_number',
        'long_description', 'generated_name', 'short_description',
        'seo_title', 'seo_description', 'change_reason',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'version_number' => 'integer',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(AiContent::class, 'content_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
