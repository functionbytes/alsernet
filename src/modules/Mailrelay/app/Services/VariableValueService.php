<?php

namespace Modules\Mailrelay\Services;

use Modules\Mailrelay\Entities\MailrelayVariable;

class VariableValueService
{
    /**
     * Get the actual value for a variable based on the provided context.
     */
    public function getValue(MailrelayVariable $variable, array $context): string
    {
        // Determine the appropriate method based on variable category
        return match ($variable->category) {
            'system' => $this->getSystemVariableValue($variable, $context),
            'customer' => $this->getCustomerVariableValue($variable, $context),
            'order' => $this->getOrderVariableValue($variable, $context),
            'document' => $this->getDocumentVariableValue($variable, $context),
            'custom' => $this->getCustomVariableValue($variable, $context),
            default => $variable->example_value ?? '',
        };
    }

    /**
     * Get value for system variables.
     */
    protected function getSystemVariableValue(MailrelayVariable $variable, array $context): string
    {
        return match ($variable->variable_key) {
            'SYSTEM_NAME' => config('app.name', 'Alsernet'),
            'SYSTEM_URL' => config('app.url', 'https://alsernet.com'),
            'SYSTEM_EMAIL' => config('mail.from.address', 'noreply@alsernet.com'),
            'CURRENT_DATE' => now()->format('Y-m-d'),
            'CURRENT_TIME' => now()->format('H:i:s'),
            'CURRENT_DATETIME' => now()->format('Y-m-d H:i:s'),
            'CURRENT_YEAR' => now()->format('Y'),
            default => $this->getSystemVariables()[$variable->variable_key] ?? $variable->example_value ?? '',
        };
    }

    /**
     * Get value for customer variables.
     */
    protected function getCustomerVariableValue(MailrelayVariable $variable, array $context): string
    {
        $customer = $context['customer'] ?? null;

        if (! $customer) {
            return $variable->example_value ?? '';
        }

        return match ($variable->variable_key) {
            'CUSTOMER_NAME' => $customer->name ?? '',
            'CUSTOMER_EMAIL' => $customer->email ?? '',
            'CUSTOMER_PHONE' => $customer->phone ?? '',
            'CUSTOMER_ID' => (string) ($customer->id ?? ''),
            'CUSTOMER_COMPANY' => $customer->company ?? '',
            default => $this->getFromObject($customer, $variable->variable_key, $variable->example_value),
        };
    }

    /**
     * Get value for order variables.
     */
    protected function getOrderVariableValue(MailrelayVariable $variable, array $context): string
    {
        $order = $context['order'] ?? null;

        if (! $order) {
            return $variable->example_value ?? '';
        }

        return match ($variable->variable_key) {
            'ORDER_ID' => (string) ($order->id ?? ''),
            'ORDER_NUMBER' => $order->order_number ?? '',
            'ORDER_TOTAL' => number_format($order->total ?? 0, 2),
            'ORDER_STATUS' => $order->status ?? '',
            'ORDER_DATE' => $order->created_at?->format('Y-m-d') ?? '',
            default => $this->getFromObject($order, $variable->variable_key, $variable->example_value),
        };
    }

    /**
     * Get value for document variables.
     */
    protected function getDocumentVariableValue(MailrelayVariable $variable, array $context): string
    {
        $document = $context['document'] ?? null;

        if (! $document) {
            return $variable->example_value ?? '';
        }

        return match ($variable->variable_key) {
            'DOCUMENT_ID' => (string) ($document->id ?? ''),
            'DOCUMENT_NUMBER' => $document->document_number ?? '',
            'DOCUMENT_TYPE' => $document->type ?? '',
            'DOCUMENT_DATE' => $document->created_at?->format('Y-m-d') ?? '',
            default => $this->getFromObject($document, $variable->variable_key, $variable->example_value),
        };
    }

    /**
     * Get value for custom variables.
     */
    protected function getCustomVariableValue(MailrelayVariable $variable, array $context): string
    {
        // Check if the variable key exists directly in the context
        $key = strtolower($variable->variable_key);

        if (isset($context[$key])) {
            return (string) $context[$key];
        }

        // Check for custom_data array in context
        if (isset($context['custom_data'][$key])) {
            return (string) $context['custom_data'][$key];
        }

        return $variable->example_value ?? '';
    }

    /**
     * Get system variables as an array.
     */
    public function getSystemVariables(): array
    {
        return [
            'SYSTEM_NAME' => config('app.name', 'Alsernet'),
            'SYSTEM_URL' => config('app.url', 'https://alsernet.com'),
            'SYSTEM_EMAIL' => config('mail.from.address', 'noreply@alsernet.com'),
            'CURRENT_DATE' => now()->format('Y-m-d'),
            'CURRENT_TIME' => now()->format('H:i:s'),
            'CURRENT_DATETIME' => now()->format('Y-m-d H:i:s'),
            'CURRENT_YEAR' => now()->format('Y'),
        ];
    }

    /**
     * Get customer variables from a customer object.
     *
     * @param  mixed  $customer
     */
    public function getCustomerVariables($customer): array
    {
        if (! $customer) {
            return [];
        }

        return [
            'CUSTOMER_NAME' => $customer->name ?? '',
            'CUSTOMER_EMAIL' => $customer->email ?? '',
            'CUSTOMER_PHONE' => $customer->phone ?? '',
            'CUSTOMER_ID' => (string) ($customer->id ?? ''),
            'CUSTOMER_COMPANY' => $customer->company ?? '',
        ];
    }

    /**
     * Get order variables from an order object.
     *
     * @param  mixed  $order
     */
    public function getOrderVariables($order): array
    {
        if (! $order) {
            return [];
        }

        return [
            'ORDER_ID' => (string) ($order->id ?? ''),
            'ORDER_NUMBER' => $order->order_number ?? '',
            'ORDER_TOTAL' => number_format($order->total ?? 0, 2),
            'ORDER_STATUS' => $order->status ?? '',
            'ORDER_DATE' => $order->created_at?->format('Y-m-d') ?? '',
        ];
    }

    /**
     * Helper method to get a value from an object by key.
     *
     * @param  mixed  $object
     * @param  mixed  $default
     */
    protected function getFromObject($object, string $key, $default = ''): string
    {
        if (! $object) {
            return (string) $default;
        }

        // Try to get the property (convert VARIABLE_KEY to variable_key)
        $property = strtolower($key);

        if (is_object($object) && isset($object->$property)) {
            return (string) $object->$property;
        }

        if (is_array($object) && isset($object[$property])) {
            return (string) $object[$property];
        }

        return (string) $default;
    }
}
