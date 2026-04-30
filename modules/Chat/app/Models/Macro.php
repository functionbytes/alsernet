<?php

namespace Modules\Chat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;

class Macro extends Model
{
    protected $table = 'chat_macros';

    // Visibility Enums
    const VISIBILITY_PERSONAL = 0;

    const VISIBILITY_GLOBAL = 1;

    const VISIBILITY_TEAM = 2;

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'visibility',
        'created_by_id',
        'updated_by_id',
        'actions',
        'conditions',
        'enabled',
    ];

    protected $casts = [
        'actions' => 'array',
        'conditions' => 'array',
        'visibility' => 'integer',
        'enabled' => 'boolean',
    ];

    protected $attributes = [
        'actions' => '[]',
        'conditions' => null,
        'enabled' => true,
    ];

    /**
     * Get the account that owns the macro
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who created the macro
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who last updated the macro
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Scope to only include enabled macros
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Check if macro has conditions
     */
    public function hasConditions(): bool
    {
        return ! empty($this->conditions);
    }
}
