<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Engagement\Database\Factories\VisitorContextFactory;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;

class VisitorContext extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'engagement_visitor_contexts';

    protected $fillable = [
        'session_token',
        'customer_id',
        'inbox_id',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function scopeForSession(Builder $query, string $sessionToken): Builder
    {
        return $query->where('session_token', $sessionToken);
    }

    public function mergeContext(array $patch): void
    {
        $current = $this->context ?? [];

        foreach ($patch as $key => $value) {
            if ($value === null) {
                unset($current[$key]);

                continue;
            }

            $current[$key] = $value;
        }

        $this->context = $current;
        $this->save();
    }

    protected static function newFactory(): VisitorContextFactory
    {
        return VisitorContextFactory::new();
    }
}
