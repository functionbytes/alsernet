<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HelpdeskTickets\Database\Factories\TicketCategoryFactory;

class TicketCategory extends Model
{
    use HasFactory;

    protected static function newFactory(): TicketCategoryFactory
    {
        return TicketCategoryFactory::new();
    }

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'order',
        'default_sla_policy_id',
        'custom_form_fields',
        'required_fields',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'custom_form_fields' => 'array',
            'required_fields' => 'array',
            'active' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Auto-increment order for new categories
        static::creating(function ($category) {
            if (is_null($category->order)) {
                $maxOrder = static::max('order') ?? 0;
                $category->order = $maxOrder + 1;
            }
        });
    }

    /**
     * Structured custom form fields for this category (builder-managed).
     *
     * Used by TicketCategoryFieldsController, PublicTicketFormController and
     * TicketCategoryValidationBuilder.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(TicketCategoryField::class, 'ticket_category_id');
    }

    /**
     * Get the default SLA policy for this category
     */
    public function defaultSlaPolicy(): BelongsTo
    {
        return $this->belongsTo(TicketSlaPolicy::class, 'default_sla_policy_id');
    }

    /**
     * Get tickets in this category
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }

    /**
     * Get ticket groups assigned to this category
     */
    public function ticketGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            TicketGroup::class,
            'helpdesk_ticket_category_ticket_group',
            'ticket_category_id',
            'ticket_group_id'
        )->withPivot(['is_default', 'priority'])
            ->withTimestamps()
            ->orderByPivot('priority');
    }

    /**
     * Get ticket canned replies linked to this category
     */
    public function ticketCannedReplies(): BelongsToMany
    {
        return $this->belongsToMany(
            TicketCannedReply::class,
            'helpdesk_ticket_category_ticket_canned_reply',
            'ticket_category_id',
            'ticket_canned_reply_id'
        )->withPivot('order')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    /**
     * Get the default group for auto-assignment
     */
    public function getDefaultGroup(): ?TicketGroup
    {
        return $this->ticketGroups()
            ->wherePivot('is_default', true)
            ->first() ?? $this->ticketGroups()->first();
    }

    /**
     * Scope to get only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get categories ordered by their sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Get the icon class for display
     */
    public function getIconClassAttribute(): string
    {
        return $this->icon ?? 'fas fa-ticket';
    }

    /**
     * Get the badge color class
     */
    public function getBadgeColorAttribute(): string
    {
        return $this->color ?? 'primary';
    }

    /**
     * Check if category has custom form fields
     */
    public function hasCustomFields(): bool
    {
        return ! empty($this->custom_form_fields);
    }

    /**
     * Get required field names
     */
    public function getRequiredFieldNames(): array
    {
        return $this->required_fields ?? [];
    }

    /**
     * Reorder categories based on an array of IDs.
     */
    public static function reorder(array $ids): void
    {
        foreach ($ids as $order => $id) {
            static::where('id', $id)->update(['order' => $order + 1]);
        }
    }
}
