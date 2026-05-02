<?php

namespace Modules\Social\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;

class Template extends Model
{
    protected $table = 'social_templates';

    protected $fillable = [
        'account_id',
        'name',
        'category',
        'content',
        'media',
        'variables',
        'hashtags',
        'networks',
        'usage_count',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'media' => 'array',
            'variables' => 'array',
            'hashtags' => 'array',
            'networks' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function replaceVariables(array $data): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $content = str_replace('{'.$key.'}', $value, $content);
        }

        return $content;
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeForNetwork($query, string $network)
    {
        return $query->whereJsonContains('networks', $network)
            ->orWhereNull('networks');
    }
}
