# Helpdesk API Documentation

Base URL: `https://system.test`
Auth: All authenticated endpoints require `Authorization: Bearer {TOKEN}` (Sanctum)
Rate limiting: All public endpoints throttle at noted limits.

---

## 1. Customer Endpoints

### GET /api/v1/helpdesk/helpdesk/customers

List paginated customers. **Requires**: `auth:sanctum` + permission `helpdesk.customers.view`.

Query params:
- `per_page` (int, default 15)
- `q` (string, optional) — searches name, email, phone

Response:
```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 1,
                "name": "Jane Doe",
                "email": "jane@example.com",
                "phone": "+52 555 123 4567",
                "language": "es",
                "timezone": "America/Mexico_City",
                "ticketsCount": 4,
                "lastSeenAt": "2026-04-18T12:00:00+00:00",
                "createdAt": "2026-01-01T00:00:00+00:00"
            }
        ],
        "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
        "meta": { "current_page": 1, "per_page": 15, "total": 42 }
    }
}
```

Error (401):
```json
{ "message": "Unauthenticated." }
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" \
  "https://system.test/api/v1/helpdesk/helpdesk/customers?per_page=20&q=jane"
```

---

### GET /api/v1/helpdesk/helpdesk/customers/{id}

Get single customer with ticket count. **Requires**: `auth:sanctum` + permission `helpdesk.customers.view`.

Path params:
- `id` (int) — customer ID

Response:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Jane Doe",
        "email": "jane@example.com",
        "phone": "+52 555 123 4567",
        "ticketsCount": 4,
        "createdAt": "2026-01-01T00:00:00+00:00"
    }
}
```

Error (404):
```json
{ "message": "No query results for model [Customer] 99" }
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" \
  https://system.test/api/v1/helpdesk/helpdesk/customers/1
```

---

### POST /api/v1/helpdesk/helpdesk/customers

Create a new customer. **Requires**: `auth:sanctum` + permission `helpdesk.customers.create`.

Request body (JSON):
```json
{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "phone": "+52 555 123 4567"
}
```

Field rules:
- `name` — required, string, max:255
- `email` — required, email, max:255, unique
- `phone` — nullable, string, max:50

Response (201):
```json
{
    "success": true,
    "message": "Cliente creado correctamente.",
    "data": {
        "id": 42,
        "name": "Jane Doe",
        "email": "jane@example.com",
        "phone": "+52 555 123 4567",
        "createdAt": "2026-04-20T10:00:00+00:00"
    }
}
```

Error (422):
```json
{
    "message": "El email es obligatorio.",
    "errors": {
        "email": ["Ya existe un cliente con ese email."]
    }
}
```

Example:
```bash
curl -X POST \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Jane Doe","email":"jane@example.com","phone":"+52 555 123 4567"}' \
  https://system.test/api/v1/helpdesk/helpdesk/customers
```

---

## 2. Canned Replies

### GET /api/v1/helpdesk/helpdesk/canned-replies

List canned replies (top 20 by usage). **Requires**: `auth:sanctum` + permission `helpdesk.cannedreplies.view`.

Query params:
- `q` (string, optional) — searches title and shortcut

Response:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Saludo inicial",
            "shortcut": "/hola",
            "body": "Hola, gracias por contactarnos...",
            "htmlBody": "<p>Hola, gracias por contactarnos...</p>",
            "tags": ["saludo", "bienvenida"]
        }
    ]
}
```

Note: Returns a flat array (not paginated — limited to 20 results ordered by `usage_count` DESC).

Example:
```bash
curl -H "Authorization: Bearer TOKEN" \
  "https://system.test/api/v1/helpdesk/helpdesk/canned-replies?q=saludo"
```

---

## 3. Ticket Endpoints (HelpdeskTickets module)

### GET /api/v1/helpdesk/tickets

List paginated tickets. **Requires**: `auth:sanctum` + permission `helpdesk.tickets.view`.

Query params:
- `per_page` (int, default 15)
- `status` (string, optional) — filter by status slug
- `category` (string, optional) — filter by category slug
- `priority` (string, optional) — `low` | `normal` | `high` | `urgent`
- `assignee_id` (int, optional) — filter by assigned agent ID
- `search` (string, optional) — searches ticket_number and subject

Response:
```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 1,
                "ticketNumber": "TK-001",
                "subject": "My printer is broken",
                "priority": "high",
                "customer": { "id": 5, "name": "Jane Doe", "email": "jane@example.com" },
                "status": { "id": 2, "name": "Open", "color": "#13C672", "slug": "open" },
                "category": { "id": 3, "name": "Hardware", "slug": "hardware" },
                "createdAt": "2026-04-20T10:00:00+00:00"
            }
        ],
        "links": { "first": "...", "last": "...", "prev": null, "next": null },
        "meta": { "current_page": 1, "per_page": 15, "total": 1 }
    }
}
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" \
  "https://system.test/api/v1/helpdesk/tickets?status=open&priority=high&per_page=20"
```

---

### POST /api/v1/helpdesk/tickets

Create a ticket. **Requires**: `auth:sanctum` + permission `helpdesk.tickets.create`.

Request body (JSON):
```json
{
    "subject": "My printer is broken",
    "description": "The printer shows error E-04 since this morning.",
    "category_id": 3,
    "priority": "high",
    "customer_id": 5
}
```

Field rules:
- `subject` — required, string, max:255
- `description` — required, string
- `category_id` — required, integer, must exist in `helpdesk_ticket_categories`
- `priority` — nullable, one of: `low`, `normal`, `high`, `urgent` (default: `normal`)
- `customer_id` — nullable, integer, must exist in `helpdesk_customers`
- `customer_email` — nullable, email, max:255 (alternative to `customer_id` — creates customer if not found)
- `customer_name` — nullable, string, max:255 (used when creating via `customer_email`)

Response (201):
```json
{
    "success": true,
    "message": "Ticket creado correctamente.",
    "data": {
        "id": 10,
        "ticketNumber": "TK-010",
        "subject": "My printer is broken",
        "priority": "high",
        "source": "api",
        "customer": { "id": 5, "name": "Jane Doe", "email": "jane@example.com" },
        "status": { "id": 1, "name": "New", "color": "#FEC90F", "slug": "new" },
        "category": { "id": 3, "name": "Hardware", "slug": "hardware" },
        "createdAt": "2026-04-20T10:00:00+00:00"
    }
}
```

Example:
```bash
curl -X POST \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"subject":"My printer is broken","description":"Error E-04","category_id":3,"priority":"high","customer_email":"jane@example.com"}' \
  https://system.test/api/v1/helpdesk/tickets
```

---

### GET /api/v1/helpdesk/tickets/{ticketNumber}

Get a single ticket. **Requires**: `auth:sanctum` + permission `helpdesk.tickets.view`.

Path params:
- `ticketNumber` (string) — e.g. `TK-010`

Response:
```json
{
    "success": true,
    "data": {
        "id": 10,
        "ticketNumber": "TK-010",
        "subject": "My printer is broken",
        "priority": "high",
        "customer": { "id": 5, "name": "Jane Doe", "email": "jane@example.com" },
        "status": { "id": 1, "name": "New", "color": "#FEC90F", "slug": "new" },
        "category": { "id": 3, "name": "Hardware", "slug": "hardware" },
        "assignee": { "id": 2, "name": "Agent Smith" },
        "createdAt": "2026-04-20T10:00:00+00:00"
    }
}
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" \
  https://system.test/api/v1/helpdesk/tickets/TK-010
```

---

### PUT /api/v1/helpdesk/tickets/{ticketNumber}

Update a ticket. **Requires**: `auth:sanctum` + permission `helpdesk.tickets.update`.

Request body (JSON) — all fields optional:
```json
{
    "subject": "Updated subject",
    "priority": "urgent",
    "status_id": 2,
    "assignee_id": 4
}
```

Response (200): Same structure as GET single ticket.

Example:
```bash
curl -X PUT \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"priority":"urgent","assignee_id":4}' \
  https://system.test/api/v1/helpdesk/tickets/TK-010
```

---

### GET /api/v1/helpdesk/tickets/{ticketNumber}/messages

List messages for a ticket. **Requires**: `auth:sanctum` + permission `helpdesk.tickets.view`.

Query params:
- `per_page` (int, default 15)

Response:
```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 1,
                "type": "message",
                "body": "The printer shows error E-04",
                "htmlBody": "<p>The printer shows error E-04</p>",
                "isInternal": false,
                "user": { "id": 2, "name": "Agent Smith" },
                "createdAt": "2026-04-20T10:05:00+00:00"
            }
        ],
        "links": {},
        "meta": { "current_page": 1, "per_page": 15, "total": 1 }
    }
}
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" \
  https://system.test/api/v1/helpdesk/tickets/TK-010/messages
```

---

### POST /api/v1/helpdesk/tickets/{ticketNumber}/messages

Add a message to a ticket. **Requires**: `auth:sanctum`.

Request body (JSON):
```json
{
    "body": "We are looking into the issue.",
    "is_internal": false
}
```

Field rules:
- `body` — required, string
- `is_internal` — boolean (default false) — internal notes are hidden from customers

Response (201):
```json
{
    "success": true,
    "message": "Mensaje enviado correctamente.",
    "data": {
        "id": 2,
        "type": "message",
        "body": "We are looking into the issue.",
        "isInternal": false,
        "user": { "id": 2, "name": "Agent Smith" },
        "createdAt": "2026-04-20T10:10:00+00:00"
    }
}
```

Example:
```bash
curl -X POST \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body":"We are looking into the issue.","is_internal":false}' \
  https://system.test/api/v1/helpdesk/tickets/TK-010/messages
```

---

## 4. Reference Endpoints (Tickets module)

### GET /api/v1/helpdesk/categories

List active ticket categories. **Requires**: `auth:sanctum` + `helpdesk.tickets.view`.

Response:
```json
{
    "success": true,
    "data": [
        { "id": 1, "name": "Hardware", "slug": "hardware", "description": "...", "icon": "fas fa-desktop", "color": "#90bb13" }
    ]
}
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" https://system.test/api/v1/helpdesk/categories
```

---

### GET /api/v1/helpdesk/statuses

List active ticket statuses. **Requires**: `auth:sanctum` + `helpdesk.tickets.view`.

Response:
```json
{
    "success": true,
    "data": [
        { "id": 1, "name": "New", "slug": "new", "color": "#FEC90F", "isOpen": true, "isDefault": true }
    ]
}
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" https://system.test/api/v1/helpdesk/statuses
```

---

### GET /api/v1/helpdesk/priorities

List active priorities. **Requires**: `auth:sanctum` + `helpdesk.tickets.view`.

Response:
```json
{
    "success": true,
    "data": [
        { "id": 1, "name": "Low", "slug": "low", "level": 1, "color": "#13C672", "responseTimeHours": 24, "resolutionTimeHours": 72 }
    ]
}
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" https://system.test/api/v1/helpdesk/priorities
```

---

### GET /api/v1/helpdesk/templates

List active ticket templates. **Requires**: `auth:sanctum` + `helpdesk.cannedreplies.view`.

Response:
```json
{
    "success": true,
    "data": [
        { "id": 1, "name": "Welcome", "description": "...", "subject": "Welcome to support", "body": "...", "category": { "id": 1, "name": "General" } }
    ]
}
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" https://system.test/api/v1/helpdesk/templates
```

---

### GET /api/v1/helpdesk/recurring-tickets

List active recurring ticket schedules (paginated). **Requires**: `auth:sanctum` + `helpdesk.tickets.view`.

Query params:
- `per_page` (int, default 15)

Response:
```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 1,
                "name": "Weekly maintenance",
                "frequency": "weekly",
                "nextRunAt": "2026-04-27T08:00:00+00:00",
                "lastRunAt": "2026-04-20T08:00:00+00:00",
                "ticketsCreated": 12,
                "category": { "id": 2, "name": "Maintenance" }
            }
        ],
        "meta": { "current_page": 1, "per_page": 15, "total": 3 }
    }
}
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" https://system.test/api/v1/helpdesk/recurring-tickets
```

---

## 5. Search

### GET /api/v1/helpdesk/search

Global search across tickets, customers, and conversations. **Requires**: `auth:sanctum` + `helpdesk.tickets.view`.

Query params:
- `q` (string, required) — min 1 character
- `type` (string, optional) — `all` | `tickets` | `customers` | `conversations` (default: `all`)

Response:
```json
{
    "success": true,
    "data": {
        "tickets": [
            { "id": 1, "ticketNumber": "TK-001", "subject": "Printer broken", "status": { "id": 2, "name": "Open", "color": "#13C672" }, "customer": { "id": 5, "name": "Jane Doe" }, "createdAt": "2026-04-20T10:00:00+00:00" }
        ],
        "customers": [
            { "id": 5, "name": "Jane Doe", "email": "jane@example.com", "phone": "+52 555 123 4567" }
        ],
        "conversations": [
            { "id": 3, "subject": "Help needed", "status": { "id": 1, "name": "Open" }, "customer": { "id": 5, "name": "Jane Doe" }, "createdAt": "2026-04-19T09:00:00+00:00" }
        ]
    }
}
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" \
  "https://system.test/api/v1/helpdesk/search?q=printer&type=tickets"
```

---

## 6. Metrics

### GET /api/v1/helpdesk/metrics/summary

Ticket metrics overview. **Requires**: `auth:sanctum` + `helpdesk.metrics.view`.

Response:
```json
{
    "success": true,
    "data": {
        "open": 24,
        "closedToday": 8,
        "slaBreached": 3,
        "unassigned": 5
    }
}
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" https://system.test/api/v1/helpdesk/metrics/summary
```

---

### GET /api/v1/helpdesk/metrics/by-agent

Per-agent ticket metrics. **Requires**: `auth:sanctum` + `helpdesk.metrics.view`.

Response:
```json
{
    "success": true,
    "data": [
        { "agentId": 2, "name": "Agent Smith", "openTickets": 7, "closedToday": 3, "slaBreached": 1 }
    ]
}
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" https://system.test/api/v1/helpdesk/metrics/by-agent
```

---

### GET /api/v1/helpdesk/ratings/summary

Customer satisfaction rating summary. **Requires**: `auth:sanctum` + `helpdesk.metrics.view`.

Response:
```json
{
    "success": true,
    "data": {
        "avgRating": 4.23,
        "totalRated": 87,
        "distribution": { "1": 3, "2": 5, "3": 12, "4": 28, "5": 39 }
    }
}
```

Example:
```bash
curl -H "Authorization: Bearer TOKEN" https://system.test/api/v1/helpdesk/ratings/summary
```

---

## 7. Inbound Email Webhooks

### POST /webhooks/helpdesk/email/{provider}

Receive inbound emails from email providers. **Public** (no auth). Rate limited: `100 req/min`. CSRF excluded.

Path params:
- `provider` (string) — `mailgun` | `sendgrid` | `postmark` | `generic`

Providers send their own payload format. The controller normalizes each provider's format internally.

Response (200):
```json
{ "status": "ok" }
```

Example (generic test):
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"from":"customer@example.com","subject":"Help!","body-plain":"I need help."}' \
  https://system.test/webhooks/helpdesk/email/mailgun
```

---

## 8. Social Channel Webhooks

All social webhooks are **public** (no auth required — Meta enforces HMAC-SHA256 signature verification internally). CSRF excluded via `webhooks/*`.

### GET /webhooks/helpdesk/whatsapp

WhatsApp webhook verification (Meta hub challenge). Returns the `hub.challenge` value.

Query params (sent by Meta):
- `hub.mode` — must be `subscribe`
- `hub.challenge` — echo value
- `hub.verify_token` — must match configured token

Response (200): Plain text challenge value.

### POST /webhooks/helpdesk/whatsapp

Receive WhatsApp Business messages. Validates `X-Hub-Signature-256` header.

Request: Meta webhook payload (see Meta Webhooks documentation).

Response (200): `{"status":"ok"}`

```bash
# Meta sends this automatically — manual testing requires HMAC signature
curl -X POST \
  -H "X-Hub-Signature-256: sha256=HMAC_VALUE" \
  -H "Content-Type: application/json" \
  -d @whatsapp_payload.json \
  https://system.test/webhooks/helpdesk/whatsapp
```

### GET /webhooks/helpdesk/facebook
### POST /webhooks/helpdesk/facebook

Same pattern as WhatsApp — Facebook Messenger verification + inbound messages.

### GET /webhooks/helpdesk/instagram
### POST /webhooks/helpdesk/instagram

Same pattern as WhatsApp — Instagram DMs verification + inbound messages.

---

## 9. Widget / Live Chat (Public)

Widget endpoints use cookie/email-based customer identity. No authentication token required.

### POST /lc/api/conversations

Start a new live chat conversation from the widget. **Public** (no auth).

Request body (JSON):
```json
{
    "customer": {
        "name": "Jane Doe",
        "email": "jane@example.com",
        "phone": "+52 555 123 4567"
    },
    "message": "Hello, I need help with my order.",
    "subject": "Order issue"
}
```

Field rules:
- `customer.name` — required, string, max:255
- `customer.email` — nullable, email, max:255 (generates guest email if omitted)
- `customer.phone` — nullable, string, max:50
- `message` — required, string, max:10000
- `subject` — nullable, string, max:255

Side effect: If an active AI agent is configured, `StartAiAgentSessionJob` is dispatched automatically.

Response (200):
```json
{
    "success": true,
    "data": {
        "conversation": {
            "id": 7,
            "subject": "Order issue",
            "status": "Open",
            "created_at": "2026-04-20T10:00:00+00:00"
        },
        "customer": { "email": "jane@example.com", "name": "Jane Doe" },
        "messages": [
            {
                "id": 1,
                "type": "message",
                "body": "Hello, I need help with my order.",
                "is_from_customer": true,
                "is_from_agent": false,
                "sender_name": "Jane Doe",
                "sender_avatar": null,
                "created_at": "2026-04-20T10:00:00+00:00"
            }
        ]
    }
}
```

Example:
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"customer":{"name":"Jane Doe","email":"jane@example.com"},"message":"Hello, I need help.","subject":"Help"}' \
  https://system.test/lc/api/conversations
```

---

### GET /lc/api/conversations/{id}

Get conversation with all public messages. **Public** (no auth). Customer verified by email match.

Query params:
- `customer_email` (string, optional) — ownership check

Response (200):
```json
{
    "success": true,
    "data": {
        "conversation": {
            "id": 7,
            "subject": "Order issue",
            "status": { "id": 1, "name": "Open", "is_open": true },
            "assignee": { "id": 2, "name": "Agent Smith" },
            "created_at": "2026-04-20T10:00:00+00:00"
        },
        "messages": [
            {
                "id": 1,
                "type": "message",
                "body": "Hello, I need help.",
                "html_body": "<p>Hello, I need help.</p>",
                "is_from_customer": true,
                "is_from_agent": false,
                "is_internal": false,
                "sender_name": "Jane Doe",
                "sender_avatar": null,
                "created_at": "2026-04-20T10:00:00+00:00"
            }
        ]
    }
}
```

Note: Internal notes (`is_internal: true`) are filtered out — customers never see them.

Example:
```bash
curl "https://system.test/lc/api/conversations/7?customer_email=jane@example.com"
```

---

### GET /lc/api/conversations/{id}/messages

Get messages for a conversation (public, internal notes excluded). **Public** (no auth).

Query params:
- `customer_email` (string, optional) — ownership check

Response (200):
```json
{
    "success": true,
    "data": {
        "messages": [
            {
                "id": 1,
                "body": "Hello, I need help.",
                "html_body": "<p>Hello, I need help.</p>",
                "is_from_customer": true,
                "is_from_agent": false,
                "sender_name": "Jane Doe",
                "attachments": [],
                "created_at": "2026-04-20T10:00:00+00:00"
            }
        ]
    }
}
```

Example:
```bash
curl "https://system.test/lc/api/conversations/7/messages?customer_email=jane@example.com"
```

---

### POST /lc/api/conversations/{id}/messages

Send a message from the widget. **Public** (no auth). Customer verified by email.

Request body (multipart/form-data to support attachments):
- `customer_email` — required, email (ownership verification)
- `message` — nullable, string, max:10000
- `attachments[]` — nullable, files (max 5 files, 10MB each, types: jpg, jpeg, png, pdf, doc, docx, txt, zip)

Note: Either `message` or `attachments` must be provided.

If conversation was closed, it is automatically reopened on customer reply.

Response (200):
```json
{
    "success": true,
    "data": {
        "message": {
            "id": 5,
            "body": "Any update?",
            "is_from_customer": true,
            "sender_name": "Jane Doe",
            "attachments": [],
            "created_at": "2026-04-20T11:00:00+00:00"
        }
    }
}
```

Example:
```bash
curl -X POST \
  -F "customer_email=jane@example.com" \
  -F "message=Any update?" \
  https://system.test/lc/api/conversations/7/messages
```

---

### POST /lc/api/conversations/{id}/reply

Agent reply to a live chat conversation. **Requires**: authenticated user with permission `manager.helpdesk.conversations.index`.

Request body (JSON):
```json
{
    "message": "We are working on it.",
    "is_internal": false
}
```

Response (200):
```json
{
    "success": true,
    "data": {
        "message": {
            "id": 6,
            "body": "We are working on it.",
            "is_from_customer": false,
            "is_from_agent": true,
            "is_internal": false,
            "sender_name": "Agent Smith",
            "created_at": "2026-04-20T11:05:00+00:00"
        }
    }
}
```

Side effect: If the conversation was unassigned, it is automatically assigned to the replying agent. First response timestamp is recorded.

Example:
```bash
curl -X POST \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"message":"We are working on it.","is_internal":false}' \
  https://system.test/lc/api/conversations/7/reply
```

---

### POST /lc/api/conversations/{id}/close

Close a conversation from the widget. **Public** (no auth). Customer verified by email.

Request body (JSON):
```json
{ "customer_email": "jane@example.com" }
```

Response (200):
```json
{
    "success": true,
    "message": "Conversación cerrada exitosamente.",
    "data": {
        "conversation": {
            "id": 7,
            "status": { "id": 3, "name": "Closed", "is_open": false }
        }
    }
}
```

Example:
```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"customer_email":"jane@example.com"}' \
  https://system.test/lc/api/conversations/7/close
```

---

## Common Error Responses

| Status | Scenario |
|--------|----------|
| 401 | Missing or invalid Bearer token |
| 403 | Authenticated but missing required permission |
| 404 | Resource not found |
| 422 | Validation failed (see `errors` key) |
| 429 | Rate limit exceeded |
| 500 | Server error (check Laravel logs) |

Standard 422 format:
```json
{
    "message": "El asunto es obligatorio.",
    "errors": {
        "subject": ["El asunto es obligatorio."],
        "category_id": ["La categoría es obligatoria."]
    }
}
```

Standard `ApiResponse::success()` wrapper:
```json
{ "success": true, "data": { ... } }
```

Standard `ApiResponse::created()` wrapper:
```json
{ "success": true, "message": "...", "data": { ... } }
```
