---
name: audit-tickets-livechat-contacts-2026
description: Security/quality audit of HelpdeskTickets, HelpdeskLivechat, HelpdeskContacts (jul-2026) — widget ticket list IDOR, untested contact merge
metadata:
  type: project
---

Audit performed 2026-07-02 on modules/HelpdeskTickets, modules/HelpdeskLivechat, modules/HelpdeskContacts.

Key finding: `WidgetTicketsController::index` (modules/HelpdeskTickets/app/Http/Controllers/Api/WidgetTicketsController.php:66) — public `GET /hd/api/tickets?email=X&website_token=Y` returns a customer's full ticket list (subject, priority, status, category, dates) keyed only by raw email match, no proof of ownership (no OTP/magic-link like the portal uses). `website_token` is embedded in the public widget JS so it's not a real secret. Only mitigated by `throttle:30,1` (modules/HelpdeskLivechat/routes/widget.php:45). This is enumeration/IDOR by design, not just missing rate limit.

Contrast: `WidgetConversationController` (HelpdeskLivechat) already fixed its IDOR via a per-conversation `widget_pubsub_token` compared in constant time (`authorizeConversation()`), backed by dedicated tests (WidgetConversationSecurityTest, ConversationOwnershipTest, EmailTranscriptSecurityTest, PreChatFormOwnershipTest) — good pattern to point to when fixing WidgetTicketsController similarly.

`ContactsMergeController` + `ContactMergeService` (modules/HelpdeskContacts) — destructive/irreversible merge (reassigns conversations/tickets/chats, soft-deletes loser) has ZERO test coverage (search/preview/execute untested; only tab endpoints and index/show are tested in HelpdeskContacts). Service itself does wrap writes in `DB::connection('helpdesk')->transaction()` (good), but no regression protection exists.

`TicketPolicy` (modules/HelpdeskTickets/app/Policies/TicketPolicy.php) is a good reference pattern: genuine `hasPermissionTo()` + `assignee_id === user.id` ownership checks, not `return true`.

`CustomerPortalController` (modules/HelpdeskTickets) is well-built: every ticket lookup scoped by `->where('customer_id', $customer->id)`, magic-link auth with RateLimiter, banned-customer check, signed URL for the no-session email rating route.

`ContactAggregatorService::erp/prestashop/syncIntegrations` make synchronous outbound HTTP-backed calls per tab click (lazy per-tab, not on page load, so less severe than a blocking dashboard load) — did not confirm timeout config on ErpContextService/PrestashopContextService, worth checking if agents report slow Contacts panel.

See also [[audit_chatflow_inbox_2026]] and [[audit_xss_false_positives]] for prior-session findings in the same Helpdesk ecosystem.
