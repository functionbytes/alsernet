<?php

namespace Modules\HelpdeskTickets\Models\Concerns;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Helpdesk\Models\CustomAttribute;

trait HasCustomAttributes
{
    /**
     * Get polymorphic relationship to custom attributes
     */
    public function customAttributes()
    {
        return $this->morphToMany(
            CustomAttribute::class,
            'attributable',
            'helpdesk_attributables',
            'attributable_id',
            'attribute_id',
            null,
            null,
            'customattribute'
        )
            ->withPivot('value')
            ->withTimestamps();
    }

    /**
     * Get a single custom attribute value by key
     */
    public function getCustomAttribute(string $key)
    {
        $attribute = $this->customAttributes()
            ->where('key', $key)
            ->first();

        if (! $attribute) {
            return null;
        }

        // Value casting is handled by CustomAttribute model accessor
        return $attribute->value;
    }

    /**
     * Set a single custom attribute value
     */
    public function setCustomAttribute(string $key, $value): void
    {
        $attribute = CustomAttribute::where('key', $key)
            ->where('type', $this->getAttributableType())
            ->where('active', true)
            ->firstOrFail();

        // Validate the value
        $this->validateAttributeValue($attribute, $value);

        // Cast value for storage
        $storedValue = CustomAttribute::castValueForStoring($value, $attribute->format);

        // Sync with pivot table (creates or updates)
        $this->customAttributes()->syncWithoutDetaching([
            $attribute->id => ['value' => $storedValue],
        ]);
    }

    /**
     * Get all custom attributes as a key-value array
     */
    public function getAllCustomAttributes(): array
    {
        return $this->customAttributes
            ->mapWithKeys(function ($attr) {
                return [$attr->key => $attr->value];
            })
            ->toArray();
    }

    /**
     * Get all custom attributes with metadata
     */
    public function getCustomAttributesWithMetadata(): array
    {
        return $this->customAttributes
            ->map(function ($attr) {
                return [
                    'id' => $attr->id,
                    'key' => $attr->key,
                    'name' => $attr->name,
                    'value' => $attr->value,
                    'type' => $attr->type,
                    'format' => $attr->format,
                    'required' => $attr->required,
                ];
            })
            ->all();
    }

    /**
     * Set multiple custom attributes at once
     */
    public function setCustomAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->setCustomAttribute($key, $value);
        }
    }

    /**
     * Update custom attributes from an array
     */
    public function updateCustomAttributes(array $attributes): void
    {
        $this->setCustomAttributes($attributes);
    }

    /**
     * Check if a custom attribute exists and is set
     */
    public function hasCustomAttribute(string $key): bool
    {
        return $this->getCustomAttribute($key) !== null;
    }

    /**
     * Delete a custom attribute value
     */
    public function deleteCustomAttribute(string $key): void
    {
        $attribute = CustomAttribute::where('key', $key)
            ->where('type', $this->getAttributableType())
            ->first();

        if ($attribute) {
            $this->customAttributes()->detach($attribute->id);
        }
    }

    /**
     * Delete all custom attributes
     */
    public function deleteAllCustomAttributes(): void
    {
        $this->customAttributes()->detach();
    }

    /**
     * Validate category-specific required attributes (for tickets)
     */
    public function validateRequiredAttributes(): void
    {
        if (! method_exists($this, 'category') || ! $this->category) {
            return;
        }

        $requiredFields = $this->category->getRequiredFieldNames() ?? [];

        if (empty($requiredFields)) {
            return;
        }

        $currentAttributes = $this->getAllCustomAttributes();
        $missing = [];

        foreach ($requiredFields as $field) {
            if (! isset($currentAttributes[$field]) || empty($currentAttributes[$field])) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            throw ValidationException::withMessages([
                'custom_fields' => 'Faltan campos obligatorios: '.implode(', ', $missing),
            ]);
        }
    }

    /**
     * Get all attributes that should be materialized (stored in JSON)
     */
    public function getMaterializedAttributes(): array
    {
        return $this->customAttributes()
            ->where('materialized', true)
            ->get()
            ->mapWithKeys(function ($attr) {
                return [$attr->key => $attr->value];
            })
            ->toArray();
    }

    /**
     * Sync materialized attributes to custom_fields JSON column
     * (if the model has a custom_fields column)
     */
    public function syncMaterializedAttributes(): void
    {
        if (! $this->hasCasts('custom_fields')) {
            return;
        }

        $materialized = $this->getMaterializedAttributes();
        $current = $this->custom_fields ?? [];

        // Merge materialized attributes into custom_fields
        $updated = array_merge($current, $materialized);

        if ($updated !== $current) {
            $this->update(['custom_fields' => $updated]);
        }
    }

    /**
     * Load materialized attributes from custom_fields JSON column
     */
    public function loadMaterializedAttributes(): void
    {
        if (! $this->hasCasts('custom_fields')) {
            return;
        }

        $fields = $this->custom_fields ?? [];

        foreach ($fields as $key => $value) {
            // Skip if already in polymorphic table
            if ($this->hasCustomAttribute($key)) {
                continue;
            }

            // Try to set from JSON column
            $attribute = CustomAttribute::where('key', $key)
                ->where('type', $this->getAttributableType())
                ->where('materialized', true)
                ->first();

            if ($attribute) {
                $this->setCustomAttribute($key, $value);
            }
        }
    }

    // ────────────────────────────────────────────────────────────────
    // Validation Methods
    // ────────────────────────────────────────────────────────────────

    /**
     * Validate a single attribute value
     */
    protected function validateAttributeValue(CustomAttribute $attribute, $value): void
    {
        // Required check
        if ($attribute->required && empty($value)) {
            throw ValidationException::withMessages([
                $attribute->key => "El campo {$attribute->name} es obligatorio",
            ]);
        }

        // Skip validation for empty optional values
        if (empty($value) && ! $attribute->required) {
            return;
        }

        // Format-specific validation
        match ($attribute->format) {
            'number' => $this->validateNumber($attribute, $value),
            'select' => $this->validateSelect($attribute, $value),
            'checkboxGroup' => $this->validateCheckboxGroup($attribute, $value),
            'date' => $this->validateDate($attribute, $value),
            'email' => $this->validateEmail($attribute, $value),
            'url' => $this->validateUrl($attribute, $value),
            default => null,
        };
    }

    /**
     * Validate number format
     */
    protected function validateNumber(CustomAttribute $attribute, $value): void
    {
        if (! is_numeric($value)) {
            throw ValidationException::withMessages([
                $attribute->key => "{$attribute->name} debe ser un número",
            ]);
        }

        $config = $attribute->config ?? [];

        if (isset($config['min']) && $value < $config['min']) {
            throw ValidationException::withMessages([
                $attribute->key => "{$attribute->name} debe ser al menos {$config['min']}",
            ]);
        }

        if (isset($config['max']) && $value > $config['max']) {
            throw ValidationException::withMessages([
                $attribute->key => "{$attribute->name} no puede ser mayor a {$config['max']}",
            ]);
        }
    }

    /**
     * Validate select format
     */
    protected function validateSelect(CustomAttribute $attribute, $value): void
    {
        $config = $attribute->config ?? [];
        $options = $config['options'] ?? [];

        if (! in_array($value, $options)) {
            throw ValidationException::withMessages([
                $attribute->key => "{$attribute->name} tiene un valor inválido",
            ]);
        }
    }

    /**
     * Validate checkbox group format
     */
    protected function validateCheckboxGroup(CustomAttribute $attribute, $value): void
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages([
                $attribute->key => "{$attribute->name} debe ser un array",
            ]);
        }

        $config = $attribute->config ?? [];
        $validOptions = $config['options'] ?? [];

        foreach ($value as $item) {
            if (! in_array($item, $validOptions)) {
                throw ValidationException::withMessages([
                    $attribute->key => "{$attribute->name} contiene opciones inválidas",
                ]);
            }
        }
    }

    /**
     * Validate date format
     */
    protected function validateDate(CustomAttribute $attribute, $value): void
    {
        try {
            Carbon::parse($value);
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                $attribute->key => "{$attribute->name} debe ser una fecha válida",
            ]);
        }
    }

    /**
     * Validate email format
     */
    protected function validateEmail(CustomAttribute $attribute, $value): void
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                $attribute->key => "{$attribute->name} debe ser un email válido",
            ]);
        }
    }

    /**
     * Validate URL format
     */
    protected function validateUrl(CustomAttribute $attribute, $value): void
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                $attribute->key => "{$attribute->name} debe ser una URL válida",
            ]);
        }
    }

    // ────────────────────────────────────────────────────────────────
    // Type Detection
    // ────────────────────────────────────────────────────────────────

    /**
     * Get the attributable type for this model
     */
    protected function getAttributableType(): string
    {
        $class = class_basename($this);

        return match ($class) {
            'Ticket' => 'ticket',
            'Customer' => 'customer',
            'Conversation' => 'conversation',
            default => throw new \Exception("Invalid attributable type: {$class}"),
        };
    }

    /**
     * Check if model has a specific cast
     */
    protected function hasCasts(string $key): bool
    {
        if (! method_exists($this, 'getCasts')) {
            return false;
        }

        return isset($this->getCasts()[$key]);
    }
}
