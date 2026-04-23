---
name: Helpdesk API conventions and structure
description: Helpdesk module API endpoints, resources, form requests, and policy registration map
type: project
---

## API Base Prefix
Routes register under `api/v1/helpdesk/` (via `HelpdeskTicketsServiceProvider::loadApiRoutes()` with prefix `api/v1/helpdesk`).

Note: Helpdesk's own `routes/api.php` adds another `helpdesk/` prefix, resulting in `/api/v1/helpdesk/helpdesk/customers` — pre-existing double-prefix, do not "fix" without confirming usage.

## Resources (modules/Helpdesk/app/Http/Resources/)
- `CustomerResource` — id, name, email, phone, avatarUrl, country, language, timezone, isBanned, isVerified, lastSeenAt, ticketsCount (whenCounted), createdAt, updatedAt
- `CannedReplyResource` — id, title, shortcut, category, content (strip_tags truncated 200), tags, isGlobal, usageCount

## Form Requests (modules/Helpdesk/app/Http/Requests/Api/)
- `IndexCustomerApiRequest` — authorize: `helpdesk.customers.view`, rules: q (nullable, min:2), per_page
- `StoreCustomerApiRequest` — authorize: `helpdesk.customers.create`, rules: name, email (unique), phone
- `IndexSearchApiRequest` — generic search request

## Response Format
Uses `Modules\Helpdesk\Http\Responses\ApiResponse` helper:
- `ApiResponse::success($data)` → 200
- `ApiResponse::created($data, $message)` → 201

## Controllers (modules/Helpdesk/app/Http/Controllers/Api/)
- `CustomersController` — index (paginated), show (withCount tickets), store
- `CannedRepliesController` — index only (limit 20, ordered by usage_count, no pagination)

## Policy Registration Map
| Policy | Model | Registered In |
|---|---|---|
| ConversationPolicy | Conversation (Helpdesk) | HelpdeskServiceProvider |
| CustomerPolicy | Customer (Helpdesk) | HelpdeskServiceProvider |
| HelpCenterArticlePolicy | HelpCenterArticle (Helpdesk) | HelpdeskServiceProvider |
| TicketPolicy | Ticket (HelpdeskTickets) | HelpdeskTicketsServiceProvider |
| CampaignPolicy | Campaign (HelpdeskCampaigns) | HelpdeskCampaignsServiceProvider |
| AiAgentFlowPolicy | AiAgentFlow (HelpdeskAgents) | HelpdeskAgentsServiceProvider |

**Why:** AiAgentFlowPolicy was missing — created at `modules/HelpdeskAgents/app/Policies/AiAgentFlowPolicy.php` and registered in `HelpdeskAgentsServiceProvider::registerPolicies()` (called from `register()`).

**How to apply:** When adding new policies to HelpdeskAgents, add to `registerPolicies()` map with `class_exists` guard.
