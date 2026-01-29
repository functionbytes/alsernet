# Acelle Form Requests Analysis

**Date:** 2026-01-29
**Analyzed Path:** `/Users/functionbytes/Function/Coding/acelle/app/Http/Requests/`
**Status:** ⚠️ Acelle does NOT use dedicated Form Request classes

---

## Executive Summary

**Critical Finding:** Acelle Mail **does not implement Laravel Form Request classes**. The codebase uses an alternative validation approach:

1. **Validation rules are defined in Model classes** (not Form Requests)
2. **Inline validation in controllers** using `$this->validate()`
3. **Custom validation messages** are sparse and mostly use Laravel defaults
4. **Authorization uses Gate facade** directly in controller methods

This is a **non-standard Laravel pattern** that differs significantly from modern Laravel best practices.

---

## 1. Directory Structure

```
/Users/functionbytes/Function/Coding/acelle/app/Http/Requests/
├── Request.php    # Base abstract class (empty)
```

### Request.php (Base Class)

```php
<?php

namespace Acelle\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class Request extends FormRequest
{
    // Empty - no implementation
}
```

**No child Form Request classes exist.** This is purely a placeholder.

---

## 2. Validation Pattern: Model-Based Rules

### 2.1 Pattern Overview

Acelle stores validation rules as **static properties or methods** within Model classes:

```php
// Pattern 1: Static Property
class MailList extends Model
{
    public static $rules = array(
        'name' => 'required',
        'from_email' => 'required|email',
        'from_name' => 'required',
        'contact.company' => 'required',
        'contact.address_1' => 'required',
        'contact.country_id' => 'required',
        'contact.state' => 'required',
        'contact.city' => 'required',
        'contact.zip' => 'required',
        'contact.phone' => 'required',
        'contact.email' => 'required|email',
        'contact.url' => 'nullable|regex:/^https{0,1}:\/\//',
        'email_subscribe' => 'nullable|regex:"^[\W]*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4}[\W]*,{1}[\W]*)*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4})[\W]*$"',
        'email_unsubscribe' => 'nullable|regex:"^[\W]*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4}[\W]*,{1}[\W]*)*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4})[\W]*$"',
        'email_daily' => 'nullable|regex:"^[\W]*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4}[\W]*,{1}[\W]*)*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4})[\W]*$"',
    );
}

// Pattern 2: Instance Method
class User extends Model
{
    public function rules()
    {
        $rules = array(
            'email' => 'required|email|unique:users,email,'.$this->id.',id',
            'first_name' => 'required',
            'last_name' => 'required',
            'timezone' => 'required',
            'language_id' => 'required',
            'image' => 'nullable|image',
        );

        if (isset($this->id)) {
            $rules['password'] = 'nullable|confirmed|min:5|max:255';
        } else {
            $rules['password'] = 'required|confirmed|min:5|max:255';
        }

        return $rules;
    }
}
```

### 2.2 Controller Usage

Controllers call validation **inline** using the `ValidatesRequests` trait:

```php
class SenderController extends Controller
{
    use ValidatesRequests; // From Controller base class

    public function store(Request $request)
    {
        $sender = new Sender();

        // Gate authorization (see section 4)
        if (\Gate::denies('create', $sender)) {
            return $this->notAuthorized();
        }

        $sender->fill($request->all());
        $sender->customer_id = $request->user()->customer->id;

        // Inline validation using model rules
        $this->validate($request, $sender->rules());

        $sender->save();

        return redirect()->action('SenderController@show', $sender->uid);
    }
}
```

---

## 3. Validation Rules Catalog

### 3.1 Common Models with Rules

| Model | Rules Method/Property | Key Fields Validated |
|-------|----------------------|---------------------|
| **User** | `rules()`, `registerRules()`, `apiRules()` | email (unique), first_name, last_name, timezone, language_id, password (conditional) |
| **Sender** | `rules()`, `editRules()` | email (unique), name |
| **MailList** | `static $rules` | name, from_email, from_name, contact fields (9 nested) |
| **Campaign** | `rules($request)` | name, subject, from_email, from_name, reply_to, tracking_domain_uid (conditional) |
| **Language** | `rules()` | name, code (unique) |
| **Currency** | `rules()` | name, code, format |
| **SendingDomain** | `static rules()` | domain (required) |
| **AdminGroup** | `static rules()` | name (required) |
| **CustomerGroup** | `static rules()` | name (required) |

### 3.2 Context-Specific Rules

Many models define **multiple rule methods** for different contexts:

#### User Model Example

```php
class User extends Model
{
    // Standard validation
    public function rules() { ... }

    // Registration-specific rules
    public function registerRules()
    {
        $rules = array(
            'email' => 'required|email|unique:users,email,'.$this->id.',id',
            'first_name' => 'required',
            'last_name' => 'required',
            'timezone' => 'required',
            'language_id' => 'required',
        );

        if (isset($this->id)) {
            $rules['password'] = 'min:5|max:255';
        } else {
            $rules['password'] = 'required|max:255';
        }

        return $rules;
    }

    // API-specific rules
    public function apiRules()
    {
        return array(
            'email' => 'required|email|unique:users,email,'.$this->id.',id',
            'first_name' => 'required',
            'last_name' => 'required',
            'timezone' => 'required',
            'language_id' => 'required',
            'password' => 'required|min:5',
        );
    }

    // Partial update rules for API
    public function apiUpdateRules($request)
    {
        $arr = [];

        if (isset($request->email)) {
            $arr['email'] = 'required|email|unique:users,email,'.$this->id.',id';
        }
        if (isset($request->first_name)) {
            $arr['first_name'] = 'required';
        }
        // ... dynamic rules based on request fields

        return $arr;
    }
}
```

### 3.3 Advanced Validation Patterns

#### Conditional Rules Based on Request

```php
class Campaign extends Model
{
    public function rules($request = null)
    {
        $rules = array(
            'name' => 'required',
            'subject' => 'required',
            'from_email' => 'required|email',
            'from_name' => 'required',
            'reply_to' => 'required|email',
        );

        // Conditional rule based on model state
        if ($this->use_default_sending_server_from_email) {
            $rules['from_email'] = 'nullable|email';
        } else {
            $rules['from_email'] = 'required|email';
        }

        // Conditional rule based on request input
        if (isset($request) && $request->custom_tracking_domain) {
            $rules['tracking_domain_uid'] = 'required';
        }

        return $rules;
    }
}
```

#### Unique Rules with Ignore Current ID

```php
// Email must be unique except for current user being edited
'email' => 'required|email|unique:users,email,'.$this->id.',id'

// Code must be unique except for current language
'code' => 'required|unique:languages,code,'.$this->id
```

### 3.4 Complex Regex Patterns

**Email List Validation (comma-separated emails):**

```php
'email_subscribe' => 'nullable|regex:"^[\W]*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4}[\W]*,{1}[\W]*)*([\w+\-.%]+@[\w\-.]+\.[A-Za-z]{2,4})[\W]*$"'
```

**URL Validation (http/https):**

```php
'contact.url' => 'nullable|regex:/^https{0,1}:\/\//'
```

---

## 4. Custom Validation Messages

### 4.1 Inline Custom Messages

Acelle **rarely uses custom validation messages**. Most validation uses Laravel's default messages from `resources/lang/en/validation.php`.

#### Example: BounceLog Model

```php
class BounceLog extends Model
{
    public static function create($params)
    {
        $rules = [
            'message_id' => 'required',
            'type' => 'in:bounced,reported',
        ];

        // Custom message for specific rule
        $messages = [
            'type.in' => 'Allowed values for type include: sent | bounced | reported | failed'
        ];

        $validator = Validator::make($params, $rules, $messages);

        if ($validator->fails()) {
            return [null, $validator];
        }

        // ... create logic
    }
}
```

#### Custom Closure Validation

```php
// Dynamic error message using closure
$rules = [
    'message_id' => [
        function ($attribute, $value, $fail) use ($params) {
            $fail("No message found with Message-ID: ".$params['message_id']);
        }
    ]
];

$validator = Validator::make($params, $rules);
```

### 4.2 Validation Message Localization

**File:** `/Users/functionbytes/Function/Coding/acelle/resources/lang/en/validation.php`

```php
return array (
    'accepted' => 'The :attribute must be accepted.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'alpha' => 'The :attribute may only contain letters.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, and dashes.',
    'alpha_num' => 'The :attribute may only contain letters and numbers.',
    'array' => 'The :attribute must be an array.',

    'attributes' => array(
        'options' => array(
            'limit_value' => 'Limit value',
            'limit_base' => 'Limit base',
            'limit_unit' => 'Limit time unit',
            'api_key' => 'API key',
            'api_secret_key' => 'API secret key',
            'username' => 'username',
            'password' => 'password',
            // ... custom attribute names
        ),
        'quota_value' => 'Sending limit',
        'quota_base' => 'Time base',
        'quota_unit' => 'Time unit',
        // ... more attribute translations
    ),

    'between' => array(
        'numeric' => 'The :attribute must be between :min and :max.',
        'file' => 'The :attribute must be between :min and :max kilobytes.',
        'string' => 'The :attribute must be between :min and :max characters.',
        'array' => 'The :attribute must have between :min and :max items.',
    ),

    'confirmed' => 'The :attribute confirmation does not match.',
    // ... standard Laravel validation messages
);
```

**Key Points:**
- Uses Laravel's standard validation messages
- Custom attribute names defined in `attributes` array
- Nested attribute names for complex forms (e.g., `lists_segments.0.mail_list_uid`)

---

## 5. Authorization in Requests

### 5.1 Authorization Pattern

Acelle uses **Gates** directly in controllers instead of `authorize()` method in Form Requests:

```php
class SenderController extends Controller
{
    public function create(Request $request)
    {
        $sender = new Sender();
        $sender->fill($request->old());

        // Authorization check using Gate
        if (\Gate::denies('create', $sender)) {
            return $this->notAuthorized();
        }

        return view('senders.create', [
            'sender' => $sender,
        ]);
    }

    public function store(Request $request)
    {
        $sender = new Sender();

        // Authorization before validation
        if (\Gate::denies('create', $sender)) {
            return $this->notAuthorized();
        }

        $sender->fill($request->all());
        $sender->customer_id = $request->user()->customer->id;

        $this->validate($request, $sender->rules());

        $sender->save();
        return redirect()->action('SenderController@show', $sender->uid);
    }
}
```

### 5.2 Authorization Response

**Controller Base Method:**

```php
class Controller extends BaseController
{
    /**
     * Check if the user is not authorized.
     *
     * @return \Illuminate\Http\Response
     */
    public function notAuthorized()
    {
        if (request()->ajax()) {
            return response()->json(['message' => trans('messages.not_authorized_message')], 403);
        }

        return response()->view('notAuthorized')->setStatusCode(403);
    }
}
```

### 5.3 Policy Structure

**File:** `/Users/functionbytes/Function/Coding/acelle/app/Policies/TemplatePolicy.php`

```php
namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\Template;

class TemplatePolicy
{
    use HandlesAuthorization;

    public function read(User $user, Template $item, $role)
    {
        switch ($role) {
            case 'admin':
                $can = $user->admin->getPermission('template_read') != 'no';
                break;
            case 'customer':
                $can = $user->customer->id == $item->customer_id || !isset($item->customer_id);
                break;
        }

        return $can;
    }

    public function create(User $user, $role)
    {
        switch ($role) {
            case 'admin':
                $can = $user->admin->getPermission('template_create') == 'yes';
                break;
            case 'customer':
                $can = true;
                break;
        }

        return $can;
    }

    public function update(User $user, Template $item, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('template_update');
                $can = $ability == 'all' || ($ability == 'own');
                break;
            case 'customer':
                $can = $user->customer->id == $item->customer_id;
                break;
        }

        return $can;
    }

    // ... more policy methods
}
```

**Key Characteristics:**
- Policies accept **$role parameter** (admin/customer)
- Permission checks use `$user->admin->getPermission()`
- Returns boolean authorization result
- No automatic HTTP response - controller handles denial

---

## 6. Validation Workflow

### 6.1 Standard Request Flow

```
1. Controller Method Invoked
   ↓
2. Gate::denies() Authorization Check
   ↓ (if denied)
   └─→ return $this->notAuthorized() → 403 Response
   ↓ (if allowed)
3. Model Instantiation
   ↓
4. Fill Model with $request->all()
   ↓
5. $this->validate($request, $model->rules())
   ↓ (if validation fails)
   └─→ ValidationException thrown → Redirect with errors
   ↓ (if validation passes)
6. Save Model
   ↓
7. Redirect to Success Route
```

### 6.2 Example: Complete CRUD Flow

```php
class UserController extends Controller
{
    // CREATE
    public function store(Request $request)
    {
        $user = new User();

        // 1. Authorization
        if (\Gate::denies('create', $user)) {
            return $this->notAuthorized();
        }

        // 2. Fill and validate
        $user->fill($request->all());
        $this->validate($request, $user->rules());

        // 3. Save
        $user->save();

        return redirect()->action('UserController@index');
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $user = User::findByUid($id);

        // 1. Authorization
        if (\Gate::denies('update', $user)) {
            return $this->notAuthorized();
        }

        // 2. Fill and validate (rules consider existing ID)
        $user->fill($request->all());
        $this->validate($request, $user->rules());

        // 3. Save
        $user->save();

        return redirect()->action('UserController@show', $user->uid);
    }
}
```

---

## 7. Comparison: Acelle vs Modern Laravel

| Aspect | Acelle Pattern | Modern Laravel Pattern |
|--------|---------------|----------------------|
| **Validation Rules** | Model methods/properties | Form Request classes |
| **Authorization** | Gate in controller | `authorize()` in Form Request |
| **Custom Messages** | Inline in controller/model | Form Request `messages()` method |
| **Rule Reusability** | Model-specific methods | Reusable Form Request classes |
| **Validation Location** | Controller (`$this->validate()`) | Automatic in Form Request |
| **Error Handling** | ValidationException auto-thrown | ValidationException auto-thrown |
| **Testing** | Must test controller + model | Can test Form Request in isolation |

### 7.1 Pros of Acelle's Approach

✅ **Validation coupled with business logic** - Rules live near the model they validate
✅ **Less boilerplate** - No need for separate Form Request files
✅ **Dynamic rules** - Easy to create context-specific rules (e.g., `apiRules()`, `registerRules()`)
✅ **Simpler for small projects** - Fewer files to maintain

### 7.2 Cons of Acelle's Approach

❌ **Not Laravel best practice** - Deviates from framework conventions
❌ **Authorization scattered** - Gate checks in every controller method
❌ **Harder to test** - Validation tied to controller/model coupling
❌ **No automatic authorization** - Must manually check Gates
❌ **Code duplication** - Gate checks repeated across controllers
❌ **Mixing concerns** - Controllers handle both authorization and validation logic

---

## 8. Migration Strategy for Mailing Module

### 8.1 Recommended Approach: Hybrid

**Use Laravel Form Requests for Mailing Module** while maintaining compatibility with Acelle patterns:

```php
// modules/Mailing/app/Http/Requests/StoreMailingListRequest.php
namespace Modules\Mailing\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMailingListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Gate::allows('create', \Modules\Mailing\app\Models\MailingList::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'from_email' => 'required|email',
            'from_name' => 'required|string|max:255',
            'contact.company' => 'required|string|max:255',
            'contact.address_1' => 'required|string|max:255',
            'contact.country_id' => 'required|exists:countries,id',
            'contact.state' => 'required|string|max:255',
            'contact.city' => 'required|string|max:255',
            'contact.zip' => 'required|string|max:20',
            'contact.phone' => 'required|string|max:50',
            'contact.email' => 'required|email',
            'contact.url' => 'nullable|url',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la lista es obligatorio.',
            'from_email.required' => 'El email de remitente es obligatorio.',
            'from_email.email' => 'El email de remitente debe ser válido.',
            'contact.company.required' => 'La empresa es obligatoria.',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre de lista',
            'from_email' => 'email de remitente',
            'from_name' => 'nombre de remitente',
            'contact.company' => 'empresa',
        ];
    }
}
```

### 8.2 Controller Integration

```php
namespace Modules\Mailing\app\Http\Controllers;

use Modules\Mailing\app\Http\Requests\StoreMailingListRequest;
use Modules\Mailing\app\Models\MailingList;

class MailingListController extends Controller
{
    /**
     * Store a newly created resource.
     *
     * Authorization and validation are handled automatically by Form Request.
     */
    public function store(StoreMailingListRequest $request)
    {
        // Request is already validated and authorized
        $mailingList = MailingList::create($request->validated());

        return redirect()
            ->route('mailing.lists.show', $mailingList)
            ->with('success', 'Lista creada exitosamente.');
    }
}
```

### 8.3 Benefits for Mailing Module

✅ **Modern Laravel conventions** - Aligns with Laravel 12 best practices
✅ **Cleaner controllers** - No validation/authorization boilerplate
✅ **Type safety** - Form Request type hints in controller methods
✅ **Easier testing** - Can test Form Requests independently
✅ **Better error messages** - Centralized Spanish translations
✅ **API-ready** - Same validation for web and API routes

---

## 9. Key Takeaways

### For Mailing Module Development

1. **Do NOT follow Acelle's validation pattern** - Use Form Requests instead
2. **Keep authorization in Form Requests** - Don't scatter Gate checks
3. **Define custom messages** - Acelle doesn't, but you should
4. **Use Spanish translations** - Acelle uses English by default
5. **Test Form Requests** - Validate rules work correctly in isolation

### Acelle Validation Anti-Patterns to Avoid

❌ Defining rules as static properties in models
❌ Multiple `rules()` methods for different contexts
❌ Manual `$this->validate()` calls in controllers
❌ Gate checks in every controller method
❌ Mixing validation logic with business logic
❌ Sparse/missing custom validation messages

### Recommended Modern Patterns

✅ One Form Request per action (Store/Update)
✅ Authorization in `authorize()` method
✅ Validation rules in `rules()` method
✅ Custom messages in `messages()` method
✅ Spanish translations in `attributes()` method
✅ Clean controllers with type-hinted Form Requests

---

## 10. Example Form Requests for Mailing Module

### StoreMailingCampaignRequest.php

```php
namespace Modules\Mailing\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMailingCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Gate::allows('create', \Modules\Mailing\app\Models\Campaign::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'from_email' => 'required|email',
            'from_name' => 'required|string|max:255',
            'reply_to' => 'required|email',
            'mail_list_id' => 'required|exists:mails_lists,id',
            'template_id' => 'nullable|exists:mails_templates,id',
            'track_open' => 'boolean',
            'track_click' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la campaña es obligatorio.',
            'subject.required' => 'El asunto del email es obligatorio.',
            'from_email.required' => 'El email de remitente es obligatorio.',
            'from_email.email' => 'El email de remitente debe ser una dirección válida.',
            'reply_to.required' => 'El email de respuesta es obligatorio.',
            'reply_to.email' => 'El email de respuesta debe ser una dirección válida.',
            'mail_list_id.required' => 'Debe seleccionar una lista de correos.',
            'mail_list_id.exists' => 'La lista seleccionada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'subject' => 'asunto',
            'from_email' => 'email de remitente',
            'from_name' => 'nombre de remitente',
            'reply_to' => 'email de respuesta',
            'mail_list_id' => 'lista de correos',
        ];
    }
}
```

### UpdateMailingSubscriberRequest.php

```php
namespace Modules\Mailing\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMailingSubscriberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subscriber = $this->route('subscriber');
        return \Gate::allows('update', $subscriber);
    }

    public function rules(): array
    {
        $subscriberId = $this->route('subscriber')->id;

        return [
            'email' => "required|email|unique:mails_subscribers,email,{$subscriberId}",
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'status' => 'required|in:subscribed,unsubscribed,bounced,spam',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El email del suscriptor es obligatorio.',
            'email.email' => 'El email debe ser una dirección válida.',
            'email.unique' => 'Este email ya está registrado.',
            'status.required' => 'El estado del suscriptor es obligatorio.',
            'status.in' => 'El estado debe ser: subscrito, dado de baja, rebotado o spam.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'email',
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'status' => 'estado',
            'tags' => 'etiquetas',
        ];
    }
}
```

---

## 11. Conclusion

**Acelle Mail does not use Laravel Form Request classes.** Instead, it employs a **model-based validation approach** with inline controller validation and Gate-based authorization.

**For the Mailing module**, we should **adopt modern Laravel 12 conventions**:

1. Create dedicated Form Request classes for each action
2. Centralize authorization in `authorize()` method
3. Define validation rules in `rules()` method
4. Provide Spanish custom messages in `messages()` method
5. Use type-hinted Form Requests in controller methods

This approach will:
- ✅ Align with Laravel best practices
- ✅ Improve code maintainability
- ✅ Enable easier testing
- ✅ Provide better error messages
- ✅ Keep controllers clean and focused

**Next Steps:**
1. Create Form Request classes for all Mailing CRUD operations
2. Define Gates/Policies for Mailing authorization
3. Write tests for Form Requests
4. Document validation rules in Spanish

---

**Report Generated:** 2026-01-29
**Analyst:** Claude Code
**Source:** Acelle Mail Codebase Analysis
