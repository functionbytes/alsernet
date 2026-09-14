<?php

namespace Modules\Supplier\Models\Ai;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'supplier_ai_audit_logs';

    protected $fillable = [
        'user_id', 'action', 'resource_type', 'resource_id',
        'model', 'cost', 'tokens', 'metadata', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'cost' => 'decimal:6',
            'tokens' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, array $attributes = []): self
    {
        return self::create(array_merge([
            'user_id' => auth()->id(),
            'action' => $action,
            'ip' => request()?->ip(),
        ], $attributes));
    }
}
