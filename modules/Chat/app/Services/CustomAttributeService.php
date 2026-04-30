<?php

namespace Modules\Chat\Services;

use Illuminate\Support\Facades\Validator;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerActivity;
use Modules\Chat\Models\Customers\CustomerAttribute;

class CustomAttributeService
{
    /**
     * Get custom attributes for a model.
     */
    public function getAttributes(Customer|Conversation $model): array
    {
        $modelType = $model instanceof Customer
            ? CustomerAttribute::MODEL_CONTACT
            : CustomerAttribute::MODEL_CONVERSATION;

        $definitions = CustomerAttribute::forAccount($model->account_id)
            ->where('attribute_model', $modelType)
            ->orderBy('attribute_display_name')
            ->get();

        $attributes = [];
        $currentValues = $model->additional_attributes ?? [];

        foreach ($definitions as $definition) {
            $value = $currentValues[$definition->attribute_key] ?? $definition->default_value;

            $attributes[] = [
                'id' => $definition->id,
                'key' => $definition->attribute_key,
                'display_name' => $definition->attribute_display_name,
                'description' => $definition->attribute_description,
                'display_type' => $definition->attribute_display_type,
                'display_type_name' => $definition->display_type_name,
                'value' => $value,
                'attribute_values' => $definition->attribute_values,
                'regex_pattern' => $definition->regex_pattern,
                'regex_cue' => $definition->regex_cue,
            ];
        }

        return $attributes;
    }

    /**
     * Update custom attributes for a model.
     */
    public function updateAttributes(Customer|Conversation $model, array $attributes): bool
    {
        $modelType = $model instanceof Customer
            ? CustomerAttribute::MODEL_CONTACT
            : CustomerAttribute::MODEL_CONVERSATION;

        $definitions = CustomerAttribute::forAccount($model->account_id)
            ->where('attribute_model', $modelType)
            ->get()
            ->keyBy('attribute_key');

        $currentAttributes = $model->additional_attributes ?? [];
        $updatedAttributes = $currentAttributes;
        $changes = [];

        foreach ($attributes as $key => $value) {
            if (! isset($definitions[$key])) {
                continue;
            }

            $definition = $definitions[$key];

            // Validate the value
            $validationResult = $this->validateAttributeValue($definition, $value);

            if (! $validationResult['valid']) {
                continue;
            }

            // Track changes
            $oldValue = $currentAttributes[$key] ?? null;
            $newValue = $validationResult['value'];

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                    'display_name' => $definition->attribute_display_name,
                ];
            }

            $updatedAttributes[$key] = $newValue;
        }

        // Update the model
        $model->update(['additional_attributes' => $updatedAttributes]);

        // Log activity if contact
        if ($model instanceof Customer && ! empty($changes)) {
            $this->logAttributeChanges($model, $changes);
        }

        return true;
    }

    /**
     * Validate attribute value based on definition.
     */
    protected function validateAttributeValue(CustomerAttribute $definition, mixed $value): array
    {
        // Handle null/empty values
        if ($value === null || $value === '') {
            return ['valid' => true, 'value' => null];
        }

        $rules = [];
        $processedValue = $value;

        switch ($definition->attribute_display_type) {
            case CustomerAttribute::DISPLAY_TYPE_NUMBER:
            case CustomerAttribute::DISPLAY_TYPE_CURRENCY:
            case CustomerAttribute::DISPLAY_TYPE_PERCENT:
                $rules = ['numeric'];
                $processedValue = (float) $value;
                break;

            case CustomerAttribute::DISPLAY_TYPE_LINK:
                $rules = ['url'];
                break;

            case CustomerAttribute::DISPLAY_TYPE_DATE:
                $rules = ['date'];
                break;

            case CustomerAttribute::DISPLAY_TYPE_LIST:
                if (! in_array($value, $definition->attribute_values ?? [])) {
                    return ['valid' => false, 'error' => 'Invalid list value'];
                }
                break;

            case CustomerAttribute::DISPLAY_TYPE_CHECKBOX:
                $processedValue = (bool) $value;
                break;

            case CustomerAttribute::DISPLAY_TYPE_TEXT:
            default:
                $rules = ['string', 'max:500'];

                // Apply regex pattern if defined
                if ($definition->regex_pattern) {
                    $rules[] = 'regex:'.$definition->regex_pattern;
                }
                break;
        }

        if (! empty($rules)) {
            $validator = Validator::make(['value' => $value], ['value' => $rules]);

            if ($validator->fails()) {
                return ['valid' => false, 'error' => $validator->errors()->first('value')];
            }
        }

        return ['valid' => true, 'value' => $processedValue];
    }

    /**
     * Log attribute changes as activity.
     */
    protected function logAttributeChanges(Customer $customer, array $changes): void
    {
        foreach ($changes as $key => $change) {
            $description = sprintf(
                'Updated %s from "%s" to "%s"',
                $change['display_name'],
                $change['old'] ?? 'empty',
                $change['new'] ?? 'empty'
            );

            CustomerActivity::log(
                $customer->id,
                'custom_attribute_updated',
                $description,
                auth()->id(),
                null,
                [
                    'attribute_key' => $key,
                    'old_value' => $change['old'],
                    'new_value' => $change['new'],
                ]
            );
        }
    }

    /**
     * Get attribute value formatted for display.
     */
    public function formatAttributeValue(CustomerAttribute $definition, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return match ($definition->attribute_display_type) {
            CustomerAttribute::DISPLAY_TYPE_CURRENCY => '$'.number_format((float) $value, 2),
            CustomerAttribute::DISPLAY_TYPE_PERCENT => number_format((float) $value, 2).'%',
            CustomerAttribute::DISPLAY_TYPE_NUMBER => number_format((float) $value, 2),
            CustomerAttribute::DISPLAY_TYPE_LINK => '<a href="'.e($value).'" target="_blank">'.e($value).'</a>',
            CustomerAttribute::DISPLAY_TYPE_DATE => date('M d, Y', strtotime($value)),
            CustomerAttribute::DISPLAY_TYPE_CHECKBOX => $value ? 'Yes' : 'No',
            default => e($value),
        };
    }

    /**
     * Get custom attributes for bulk operations.
     */
    public function getContactAttributeDefinitions(int $accountId): array
    {
        return CustomerAttribute::forAccount($accountId)
            ->forContacts()
            ->orderBy('attribute_display_name')
            ->get()
            ->map(function ($definition) {
                return [
                    'id' => $definition->id,
                    'key' => $definition->attribute_key,
                    'display_name' => $definition->attribute_display_name,
                    'description' => $definition->attribute_description,
                    'display_type' => $definition->attribute_display_type,
                    'display_type_name' => $definition->display_type_name,
                    'attribute_values' => $definition->attribute_values,
                ];
            })
            ->toArray();
    }
}
