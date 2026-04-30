<?php

namespace Modules\Chat\Models\Customers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Chat\Models\Accounts\Account;

class CustomerAttribute extends Model
{
    protected $table = 'chat_attributes';

    // Display Type Enums
    const DISPLAY_TYPE_TEXT = 0;

    const DISPLAY_TYPE_NUMBER = 1;

    const DISPLAY_TYPE_CURRENCY = 2;

    const DISPLAY_TYPE_PERCENT = 3;

    const DISPLAY_TYPE_LINK = 4;

    const DISPLAY_TYPE_DATE = 5;

    const DISPLAY_TYPE_LIST = 6;

    const DISPLAY_TYPE_CHECKBOX = 7;

    // Model Type Enums
    const MODEL_CONTACT = 0;

    const MODEL_CONVERSATION = 1;

    protected $fillable = [
        'attribute_display_name',
        'attribute_key',
        'attribute_display_type',
        'default_value',
        'attribute_model',
        'account_id',
        'attribute_description',
        'attribute_values',
        'regex_pattern',
        'regex_cue',
    ];

    protected $casts = [
        'attribute_values' => 'array',
        'attribute_display_type' => 'integer',
        'attribute_model' => 'integer',
    ];

    /**
     * Get the account that owns the attribute definition.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope to get only contact attributes.
     */
    public function scopeForContacts(Builder $query): Builder
    {
        return $query->where('attribute_model', self::MODEL_CONTACT);
    }

    /**
     * Scope to get only conversation attributes.
     */
    public function scopeForConversations(Builder $query): Builder
    {
        return $query->where('attribute_model', self::MODEL_CONVERSATION);
    }

    /**
     * Scope by account.
     */
    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope to get only customer attributes.
     */
    public function scopeForCustomers(Builder $query): Builder
    {
        return $query->where('attribute_model', self::MODEL_CONTACT);
    }

    /**
     * Get display type name.
     */
    public function getDisplayTypeNameAttribute(): string
    {
        return match ($this->attribute_display_type) {
            self::DISPLAY_TYPE_TEXT => 'Text',
            self::DISPLAY_TYPE_NUMBER => 'Number',
            self::DISPLAY_TYPE_CURRENCY => 'Currency',
            self::DISPLAY_TYPE_PERCENT => 'Percent',
            self::DISPLAY_TYPE_LINK => 'Link',
            self::DISPLAY_TYPE_DATE => 'Date',
            self::DISPLAY_TYPE_LIST => 'List',
            self::DISPLAY_TYPE_CHECKBOX => 'Checkbox',
            default => 'Unknown',
        };
    }

    /**
     * Get model type name.
     */
    public function getModelTypeNameAttribute(): string
    {
        return match ($this->attribute_model) {
            self::MODEL_CONTACT => 'Customers',
            self::MODEL_CONVERSATION => 'Conversation',
            default => 'Unknown',
        };
    }

    /**
     * Check if attribute is of list type.
     */
    public function isListType(): bool
    {
        return $this->attribute_display_type === self::DISPLAY_TYPE_LIST;
    }

    /**
     * Check if attribute is of checkbox type.
     */
    public function isCheckboxType(): bool
    {
        return $this->attribute_display_type === self::DISPLAY_TYPE_CHECKBOX;
    }

    /**
     * Get all display types.
     */
    public static function getDisplayTypes(): array
    {
        return [
            self::DISPLAY_TYPE_TEXT => 'Text',
            self::DISPLAY_TYPE_NUMBER => 'Number',
            self::DISPLAY_TYPE_CURRENCY => 'Currency',
            self::DISPLAY_TYPE_PERCENT => 'Percent',
            self::DISPLAY_TYPE_LINK => 'Link',
            self::DISPLAY_TYPE_DATE => 'Date',
            self::DISPLAY_TYPE_LIST => 'List',
            self::DISPLAY_TYPE_CHECKBOX => 'Checkbox',
        ];
    }

    /**
     * Get all model types.
     */
    public static function getModelTypes(): array
    {
        return [
            self::MODEL_CONTACT => 'Customers Attribute',
            self::MODEL_CONVERSATION => 'Conversation Attribute',
        ];
    }
}
