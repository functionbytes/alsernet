<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Mailing\Traits\HasUid;

class TemplateForm extends Model
{
    use HasFactory, HasUid;

    protected $table = 'mailing_template_forms';

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'template_id',
        'form_name',
        'form_fields',
        'form_attributes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'form_fields' => 'json',
            'form_attributes' => 'json',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the template that owns this form.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get form fields as an array.
     */
    public function getFieldsArray(): array
    {
        if (empty($this->form_fields)) {
            return [];
        }

        return is_array($this->form_fields) ? $this->form_fields : json_decode($this->form_fields, true) ?? [];
    }

    /**
     * Get form attributes as an array.
     */
    public function getAttributesArray(): array
    {
        if (empty($this->form_attributes)) {
            return [];
        }

        return is_array($this->form_attributes) ? $this->form_attributes : json_decode($this->form_attributes, true) ?? [];
    }

    /**
     * Validate form data against the form fields definition.
     */
    public function validateForm(array $data): bool
    {
        $fields = $this->getFieldsArray();

        if (empty($fields)) {
            return true;
        }

        foreach ($fields as $field) {
            $fieldName = $field['name'] ?? null;

            if (empty($fieldName)) {
                continue;
            }

            // Check if field is required and validate
            if (($field['required'] ?? false) && empty($data[$fieldName])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a field definition by name.
     */
    public function getField(string $fieldName): ?array
    {
        $fields = $this->getFieldsArray();

        foreach ($fields as $field) {
            if (($field['name'] ?? null) === $fieldName) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Add a field to the form.
     */
    public function addField(array $field): void
    {
        $fields = $this->getFieldsArray();
        $fields[] = $field;
        $this->form_fields = $fields;
    }

    /**
     * Remove a field from the form by name.
     */
    public function removeField(string $fieldName): void
    {
        $fields = $this->getFieldsArray();
        $this->form_fields = array_filter($fields, function ($field) use ($fieldName) {
            return ($field['name'] ?? null) !== $fieldName;
        });
    }

    /**
     * Update a field definition by name.
     */
    public function updateField(string $fieldName, array $updates): void
    {
        $fields = $this->getFieldsArray();
        $found = false;

        foreach ($fields as &$field) {
            if (($field['name'] ?? null) === $fieldName) {
                $field = array_merge($field, $updates);
                $found = true;
                break;
            }
        }

        if ($found) {
            $this->form_fields = $fields;
        }
    }
}
