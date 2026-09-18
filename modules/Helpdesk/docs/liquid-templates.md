# Liquid Templates — Helpdesk Module

Liquid is a safe, sandboxed template engine used in Helpdesk for dynamic text
in Macros, Automation Actions, Canned Replies, and outbound messages.

---

## Available Variables

### `contact` — ContactDrop

Represents the customer who initiated the conversation.

| Variable | Type | Description |
|----------|------|-------------|
| `contact.id` | integer | Customer ID |
| `contact.name` | string | Full name |
| `contact.email` | string | Email address |
| `contact.phone` | string | Phone number |
| `contact.company` | string | Company name |
| `contact.created_at` | ISO8601 string | Registration date |
| `contact.custom_attributes` | object | Custom attribute key-values |

### `conversation` — ConversationDrop

| Variable | Type | Description |
|----------|------|-------------|
| `conversation.id` | integer | Conversation ID |
| `conversation.subject` | string | Subject line |
| `conversation.status` | string | Status name (e.g. "Open", "Closed") |
| `conversation.priority` | string | Priority (low, medium, high, urgent) |
| `conversation.channel` | string | Channel slug (email, whatsapp, web, etc.) |
| `conversation.created_at` | ISO8601 string | Creation date |
| `conversation.assignee` | AgentDrop or null | Assigned agent |
| `conversation.inbox` | InboxDrop or null | Inbox this conversation belongs to |

### `conversation.assignee` / `agent` — AgentDrop

| Variable | Type | Description |
|----------|------|-------------|
| `agent.id` | integer | User ID |
| `agent.firstname` | string | First name |
| `agent.lastname` | string | Last name |
| `agent.name` | string | Full name (firstname + lastname) |
| `agent.email` | string | Email address |

### `conversation.inbox` / `inbox` — InboxDrop

| Variable | Type | Description |
|----------|------|-------------|
| `inbox.id` | integer | Inbox ID |
| `inbox.name` | string | Inbox display name |
| `inbox.channel_type` | string | Channel type slug |

---

## Usage Examples

### Basic variable substitution

```liquid
Hola {{ contact.name }},

Gracias por contactarnos a través de {{ inbox.name }}.
Tu conversación #{{ conversation.id }} ha sido recibida y será atendida pronto.

Saludos,
{{ agent.name }}
```

### Conditional blocks

```liquid
{% if conversation.priority == "urgent" %}
Tu solicitud ha sido marcada como URGENTE y será atendida de inmediato.
{% else %}
Tu solicitud será atendida en el orden recibido.
{% endif %}
```

### Assignee check

```liquid
{% if conversation.assignee %}
Tu caso está siendo atendido por {{ conversation.assignee.name }}.
{% else %}
Tu caso será asignado a un agente próximamente.
{% endif %}
```

### Custom attributes

```liquid
{% assign plan = contact.custom_attributes.plan %}
{% if plan == "premium" %}
Como cliente Premium, tienes prioridad de atención.
{% endif %}
```

---

## Rendering in PHP

### Via the service directly

```php
use Modules\Helpdesk\Services\Templates\LiquidRenderer;

$renderer = app(LiquidRenderer::class);

// Render with arbitrary context
$output = $renderer->render('Hola {{ name }}', ['name' => 'Juan']);

// Render with a full Conversation object (builds context automatically)
$output = $renderer->renderForConversation($template, $conversation);

// Render with extra variables
$output = $renderer->renderForConversation($template, $conversation, [
    'company_name' => 'Acme Corp',
]);

// Validate before saving
$validation = $renderer->isValid($template);
if (! $validation['valid']) {
    // $validation['errors'] contains error messages
}
```

---

## Built-in Liquid Filters

These Liquid built-in filters work out of the box:

| Filter | Example | Result |
|--------|---------|--------|
| `upcase` | `{{ name \| upcase }}` | `JUAN` |
| `downcase` | `{{ name \| downcase }}` | `juan` |
| `capitalize` | `{{ name \| capitalize }}` | `Juan` |
| `strip` | `{{ name \| strip }}` | trims whitespace |
| `truncate: N` | `{{ body \| truncate: 50 }}` | first 50 chars |
| `date: format` | `{{ created_at \| date: "%d/%m/%Y" }}` | `05/05/2026` |
| `default: value` | `{{ phone \| default: "Sin teléfono" }}` | fallback when blank |
| `size` | `{{ items \| size }}` | count of array |
| `first` | `{{ items \| first }}` | first element |
| `last` | `{{ items \| last }}` | last element |
| `join: sep` | `{{ tags \| join: ", " }}` | join array |
| `split: sep` | `{{ "a,b" \| split: "," }}` | split string |
| `replace: old, new` | `{{ text \| replace: "foo", "bar" }}` | replace |
| `remove: str` | `{{ text \| remove: "draft" }}` | remove substring |
| `newline_to_br` | `{{ body \| newline_to_br }}` | `\n` → `<br>` |
| `strip_html` | `{{ html_body \| strip_html }}` | removes HTML tags |
| `escape` | `{{ user_input \| escape }}` | HTML-escape |
| `url_encode` | `{{ query \| url_encode }}` | URL-encode |

---

## Template Caching

Parsed templates are cached in Redis for 1 hour keyed by `liquid:tpl:{md5(template)}`.
This means the first render of a new template string pays the parsing cost; subsequent
renders with the same content are near-instant.

To invalidate the cache after updating a template, either wait for TTL expiry or run:

```bash
php artisan cache:clear
```

---

## Security

Liquid is sandboxed by design. Templates cannot:

- Execute arbitrary PHP code
- Access environment variables or configuration
- Make HTTP requests
- Read files from the filesystem
- Access any object not explicitly passed in the context

Only the Drop properties and methods explicitly declared in each Drop class are
accessible from Liquid templates.
