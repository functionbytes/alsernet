---
name: audit_xss_false_positives
description: Blade {{ json_encode() }} in data-* HTML attributes is safe — htmlspecialchars with ENT_QUOTES encodes both < > " and '. jQuery .data() decodes safely.
metadata:
  type: feedback
---

`{{ json_encode($data) }}` in `data-*` HTML attributes IS safe because Blade `{{ }}` applies `htmlspecialchars()` with `ENT_QUOTES | ENT_SUBSTITUTE`, encoding `"` to `&quot;` and `<>` to entities. jQuery `.data()` auto-parses and HTML-decodes the attribute correctly.

**Why:** T0.26 audit (B3-xss package) claimed XSS in `audits/index.blade.php` data-properties and `macros/edit.blade.php` — both were FALSE POSITIVES. Neither file uses `{!! !!}`. The pattern `{{ json_encode() }}` in HTML attributes is secure.

**How to apply:** Do not flag `{{ json_encode($x) }}` in HTML `data-*` attributes as XSS. Use `@json()` directive only for values inside `<script>` blocks. The `{{ }}` pattern is correct for HTML attributes.
