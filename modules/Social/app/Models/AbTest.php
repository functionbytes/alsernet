<?php

namespace Modules\Social\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;

class AbTest extends Model
{
    protected $table = 'social_ab_tests';

    protected $fillable = [
        'account_id',
        'name',
        'variant_a_post_id',
        'variant_b_post_id',
        'metric',
        'status',
        'variant_a_score',
        'variant_b_score',
        'winner_post_id',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function variantA(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'variant_a_post_id');
    }

    public function variantB(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'variant_b_post_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'winner_post_id');
    }

    public function calculateScores(): void
    {
        $variantA = $this->variantA;
        $variantB = $this->variantB;

        $this->variant_a_score = match ($this->metric) {
            'engagement' => $variantA->likes_count + $variantA->comments_count + $variantA->shares_count,
            'likes' => $variantA->likes_count,
            'comments' => $variantA->comments_count,
            'clicks' => $variantA->clicks_count,
            default => 0,
        };

        $this->variant_b_score = match ($this->metric) {
            'engagement' => $variantB->likes_count + $variantB->comments_count + $variantB->shares_count,
            'likes' => $variantB->likes_count,
            'comments' => $variantB->comments_count,
            'clicks' => $variantB->clicks_count,
            default => 0,
        };

        $this->save();
    }

    public function determineWinner(): ?Post
    {
        if ($this->variant_a_score > $this->variant_b_score) {
            $this->update(['winner_post_id' => $this->variant_a_post_id]);

            return $this->variantA;
        } elseif ($this->variant_b_score > $this->variant_a_score) {
            $this->update(['winner_post_id' => $this->variant_b_post_id]);

            return $this->variantB;
        }

        return null; // Tie
    }

    public function complete(): void
    {
        $this->calculateScores();
        $this->determineWinner();
        $this->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
