# Security Agent Memory Index

## Audit findings
- [XSS audit false positives — json_encode in data attributes](audit_xss_false_positives.md) — Blade {{ json_encode() }} in data-* attrs IS safe; {{ }} applies ENT_QUOTES htmlspecialchars
- [ChatFlow + Inbox audit Jun 2026](audit_chatflow_inbox_2026.md) — IDOR in takeOver (mitigated by role gate), missing auth on reactToMessage/forwardMessage/messageInfo, import no file validation, business event launcher no email validation
