<?php

namespace Modules\Supplier\Models\Automation;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Modules\Supplier\Database\Factories\Automation\AutomationVariableFactory;

/**
 * Supplier Automation Variable Model
 *
 * Global and scoped variables for automation workflows.
 *
 * @property int $id
 * @property string $uid
 * @property string $name
 * @property string|null $description
 * @property string $scope
 * @property int|null $scope_id
 * @property string $variable_type
 * @property string|null $value
 * @property mixed|null $encrypted_value
 * @property string|null $validation_regex
 * @property array|null $allowed_values
 * @property bool $is_required
 * @property string|null $default_value
 * @property bool $is_system
 * @property bool $is_sensitive
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AutomationVariable extends Model
{
    protected $table = 'supplier_automation_variables';

    use HasFactory, HasUid;

    protected $fillable = [
        'uid',
        'name',
        'description',
        'scope',
        'scope_id',
        'variable_type',
        'value',
        'encrypted_value',
        'validation_regex',
        'allowed_values',
        'is_required',
        'default_value',
        'is_system',
        'is_sensitive',
    ];

    protected function casts(): array
    {
        return [
            'scope_id' => 'integer',
            'allowed_values' => 'array',
            'is_required' => 'boolean',
            'is_system' => 'boolean',
            'is_sensitive' => 'boolean',
        ];
    }

    protected static function newFactory(): AutomationVariableFactory
    {
        return AutomationVariableFactory::new();
    }

    /**
     * Scope: Filter by scope type
     */
    public function scopeOfScope(Builder $query, string $scope, ?int $scopeId = null): Builder
    {
        $query->where('scope', $scope);

        if ($scopeId !== null) {
            $query->where('scope_id', $scopeId);
        }

        return $query;
    }

    /**
     * Scope: Get global variables
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('scope', 'global');
    }

    /**
     * Scope: Get supplier-scoped variables
     */
    public function scopeSupplierScope(Builder $query, ?int $supplierId = null): Builder
    {
        return $this->scopeOfScope($query, 'supplier', $supplierId);
    }

    /**
     * Scope: Get source-scoped variables
     */
    public function scopeSourceScope(Builder $query, ?int $sourceId = null): Builder
    {
        return $this->scopeOfScope($query, 'source', $sourceId);
    }

    /**
     * Scope: Get workflow-scoped variables
     */
    public function scopeWorkflowScope(Builder $query, ?int $workflowId = null): Builder
    {
        return $this->scopeOfScope($query, 'workflow', $workflowId);
    }

    /**
     * Scope: Get system variables
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope: Get non-system variables
     */
    public function scopeUserDefined(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope: Get sensitive variables
     */
    public function scopeSensitive(Builder $query): Builder
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Scope: Filter by variable type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('variable_type', $type);
    }

    /**
     * Get the actual value (decrypts if needed)
     */
    public function getValue(): mixed
    {
        if ($this->variable_type === 'secret' && $this->encrypted_value) {
            return Crypt::decryptString($this->encrypted_value);
        }

        if ($this->variable_type === 'json' && $this->value) {
            return json_decode($this->value, true);
        }

        if ($this->variable_type === 'boolean') {
            return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->variable_type === 'number') {
            return is_numeric($this->value) ? (float) $this->value : null;
        }

        return $this->value;
    }

    /**
     * Set the value (encrypts if needed)
     */
    public function setValue(mixed $value): void
    {
        if ($this->variable_type === 'secret') {
            $this->encrypted_value = Crypt::encryptString($value);
            $this->value = null;
        } elseif ($this->variable_type === 'json') {
            $this->value = is_string($value) ? $value : json_encode($value);
            $this->encrypted_value = null;
        } elseif ($this->variable_type === 'boolean') {
            $this->value = $value ? 'true' : 'false';
            $this->encrypted_value = null;
        } else {
            $this->value = (string) $value;
            $this->encrypted_value = null;
        }
    }

    /**
     * Validate the value against defined rules
     */
    public function validate(mixed $value): bool
    {
        // Check allowed values
        if ($this->allowed_values && ! in_array($value, $this->allowed_values)) {
            return false;
        }

        // Check regex validation
        if ($this->validation_regex && ! preg_match($this->validation_regex, (string) $value)) {
            return false;
        }

        return true;
    }

    /**
     * Check if this is a global variable
     */
    public function isGlobal(): bool
    {
        return $this->scope === 'global';
    }

    /**
     * Check if this is a supplier-scoped variable
     */
    public function isSupplierScoped(): bool
    {
        return $this->scope === 'supplier';
    }

    /**
     * Check if this is a source-scoped variable
     */
    public function isSourceScoped(): bool
    {
        return $this->scope === 'source';
    }

    /**
     * Check if this is a workflow-scoped variable
     */
    public function isWorkflowScoped(): bool
    {
        return $this->scope === 'workflow';
    }

    /**
     * Check if this is a secret variable
     */
    public function isSecret(): bool
    {
        return $this->variable_type === 'secret';
    }

    /**
     * Get variable by name and scope
     */
    public static function getVariable(string $name, string $scope = 'global', ?int $scopeId = null): ?self
    {
        return self::where('name', $name)
            ->where('scope', $scope)
            ->where('scope_id', $scopeId)
            ->first();
    }

    /**
     * Get or create a variable
     */
    public static function getOrCreate(
        string $name,
        string $variableType,
        string $scope = 'global',
        ?int $scopeId = null,
        mixed $defaultValue = null
    ): self {
        $variable = self::getVariable($name, $scope, $scopeId);

        if (! $variable) {
            $variable = self::create([
                'name' => $name,
                'variable_type' => $variableType,
                'scope' => $scope,
                'scope_id' => $scopeId,
                'value' => $defaultValue,
            ]);
        }

        return $variable;
    }

    /**
     * Get all variables for a specific scope as key-value array
     */
    public static function getVariablesForScope(string $scope, ?int $scopeId = null): array
    {
        $variables = self::where('scope', $scope)
            ->where('scope_id', $scopeId)
            ->get();

        $result = [];
        foreach ($variables as $variable) {
            $result[$variable->name] = $variable->getValue();
        }

        return $result;
    }
}
