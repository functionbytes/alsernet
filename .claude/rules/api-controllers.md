---
globs: "modules/*/app/Http/Controllers/Api/**/*.php"
---

# API Controller Rules

- Always use Sanctum: `auth:sanctum` middleware
- Always return API Resources (never raw arrays or models)
- JSON response format: `{ success: bool, message: string, data: Resource }`
- Status codes: 200 GET/PUT, 201 POST, 204 DELETE, 404 not found, 422 validation
- camelCase for JSON response keys
- ISO8601 for all date fields: `->toIso8601String()`
- Paginate ALL list endpoints: `->paginate($request->input('per_page', 15))`
- Wrap writes in `DB::transaction()`
- Use Form Requests for validation (never inline)
