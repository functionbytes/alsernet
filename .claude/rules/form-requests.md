---
globs: "modules/*/app/Http/Requests/**/*.php"
---

# Form Request Rules

## Structure Required

```php
<?php

namespace Modules\{ModuleName}\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Store{Entity}Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('{alias}.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'is_active' => 'estado',
        ];
    }
}
```

## Rules

- **Naming**: `Store{Entity}Request`, `Update{Entity}Request`, `{Action}{Entity}Request`
- **authorize()**: ALWAYS check Spatie permission with `{alias}.action` convention
- **rules()**: use array syntax `['required', 'string']` (NOT pipe `'required|string'`)
- **Validation rules**: explicit size/format (`'max:255'`, `'in:pending,done'`)
- **Messages in Spanish**: always define Spanish error messages
- **Attributes in Spanish**: for better error output
- **Nullable fields**: use `['nullable', ...]` explicitly
- **Booleans**: use `['boolean']` for checkboxes (handle with `$request->has()` in controller if needed)
- **Foreign keys**: use `['exists:table,id']` for validation
- **Unique with update**: use `Rule::unique('table')->ignore($this->route('resource'))`

## Common Validation Patterns

### Email unique with ignore on update
```php
use Illuminate\Validation\Rule;

'email' => [
    'required',
    'email',
    Rule::unique('users', 'email')->ignore($this->route('user')),
],
```

### Array of items
```php
'items' => ['required', 'array', 'min:1'],
'items.*' => ['integer', 'exists:items,id'],
```

### Nested validation
```php
'addresses' => ['required', 'array'],
'addresses.*.street' => ['required', 'string'],
'addresses.*.city' => ['required', 'string'],
```

### File upload
```php
'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,png,doc,docx'],
```

### Color picker (hex)
```php
'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
```

### Icon (Font Awesome)
```php
'icon' => ['nullable', 'string', 'regex:/^(fas|far|fab) fa-[a-z0-9-]+$/'],
```

## Form Requests con checkboxes

Checkboxes no envian valor cuando estan `unchecked`. Manejar en controller:
```php
$data = $request->safe()->all();
foreach (['is_active', 'is_featured'] as $checkbox) {
    $data[$checkbox] = $request->has($checkbox) ? '1' : '0';
}
```

## NO usar en Form Request

- Inline validation en controller (`$request->validate(...)`)
- `$guarded` en el modelo (usar `$fillable` explicito)
- Authorization en controller con `if/throw` (usar `authorize()` method)

## Ver tambien

- [ui-patterns/form-patterns.md] para patrones del form en Blade
- [rules/controllers.md] para como usar Form Requests en controllers
