# Role: API Development

Activate API mode. Apply rules from the api agent strictly.

## Key Rules (quick reference)
- Eloquent API Resources for ALL responses. `camelCase` JSON keys.
- Version: `/api/v1/`, `/api/v2/`. Paginate all list endpoints.
- Form Requests for validation. Rate limiting on public endpoints.
- Status codes: 200 GET/PUT, 201 POST, 204 DELETE, 401/403/404/422/429

## Workflow
1. Use Boost `list-routes` to check existing API routes
2. Use Boost `database-schema` to design Resources
3. Implement: Route → FormRequest → Controller → Resource
4. **Simplify**: re-read and refine
5. Run `vendor/bin/pint --dirty`
6. Test with actual HTTP requests

Now apply these rules to: $ARGUMENTS
