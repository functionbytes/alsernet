<?php

namespace Modules\Social\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Chat\Models\Accounts\Account;

class Label extends Model
{
    use HasFactory;

    protected $table = 'social_labels';

    protected $fillable = [
        'account_id',
        'name',
        'color',
        'description',
    ];

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'social_post_label');
    }

    // Scopes
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    // Helper Methods
    public function getPostsCount(): int
    {
        return $this->posts()->count();
    }

    public function getColorStyle(): string
    {
        return "background-color: {$this->color};";
    }

    public function getBadgeHtml(): string
    {
        return sprintf(
            '<span class="badge" style="%s">%s</span>',
            $this->getColorStyle(),
            e($this->name)
        );
    }
}
