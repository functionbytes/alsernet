<?php

namespace Modules\Helpdesk\Concerns;

use Modules\Helpdesk\Models\CustomField;
use Modules\Helpdesk\Models\CustomFieldValue;

trait HasCustomFields
{
    /**
     * Get the entity_type string for this model.
     */
    protected function customFieldEntityType(): string
    {
        return match (class_basename($this)) {
            'Customer' => 'customer',
            'Conversation' => 'conversation',
            default => throw new \LogicException('HasCustomFields: unsupported model '.static::class),
        };
    }

    /**
     * Get the value of a single custom field by key.
     */
    public function customField(string $key): mixed
    {
        $field = CustomField::where('entity_type', $this->customFieldEntityType())
            ->where('key', $key)
            ->first();

        if (! $field) {
            return null;
        }

        $cfv = CustomFieldValue::where('field_id', $field->id)
            ->where('entity_type', $this->customFieldEntityType())
            ->where('entity_id', $this->getKey())
            ->first();

        return $cfv ? $cfv->getTypedValue() : $field->default_value;
    }

    /**
     * Set (upsert) a custom field value by key.
     */
    public function setCustomField(string $key, mixed $value): void
    {
        $field = CustomField::where('entity_type', $this->customFieldEntityType())
            ->where('key', $key)
            ->firstOrFail();

        $stored = is_array($value) ? json_encode($value) : (string) $value;

        CustomFieldValue::updateOrCreate(
            [
                'field_id' => $field->id,
                'entity_type' => $this->customFieldEntityType(),
                'entity_id' => $this->getKey(),
            ],
            ['value' => $stored]
        );
    }

    /**
     * Return all custom fields and their current values as key => value.
     */
    public function getCustomFields(): array
    {
        $fields = CustomField::where('entity_type', $this->customFieldEntityType())
            ->ordered()
            ->get();

        $values = CustomFieldValue::where('entity_type', $this->customFieldEntityType())
            ->where('entity_id', $this->getKey())
            ->with('field')
            ->get()
            ->keyBy('field_id');

        return $fields->map(function (CustomField $field) use ($values) {
            $cfv = $values->get($field->id);

            return [
                'field' => $field,
                'value' => $cfv ? $cfv->getTypedValue() : $field->default_value,
            ];
        })->all();
    }
}
