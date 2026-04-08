<?php

namespace Modules\Helpdesk\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasUid, SoftDeletes;

    protected $table = 'helpdesk_categories';

    protected $fillable = [
        'uid',
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'parent_id',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Tickets in this category
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }

    /**
     * Canned replies for this category
     */
    public function cannedReplies(): HasMany
    {
        return $this->hasMany(CannedReply::class, 'category_id');
    }

    /**
     * SLA policies for this category
     */
    public function slaPolicies(): HasMany
    {
        return $this->hasMany(SlaPolicy::class, 'category_id');
    }
}
