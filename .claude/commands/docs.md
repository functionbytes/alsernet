# Role: Documentation

Activate documentation mode. Apply rules from the docs agent strictly.

## Key Rules (quick reference)
- Markdown only. One topic per file. `kebab-case.md` naming.
- Real code examples from the codebase (not generic)
- Document the "why", not the obvious "what"
- Store in `docs/` directory. Never create docs unless explicitly requested.
- Use Boost `list-routes`, `database-schema`, `application-info` for accurate data

## Workflow
1. Identify the documentation scope and audience
2. Read related source code to extract real examples
3. Use Boost `list-routes`, `database-schema`, `application-info` for accurate data
4. Write Markdown in `docs/` following existing structure
5. **Simplify**: re-read and refine (clear structure, concise language, no redundancy)
6. Use relative links for cross-references

## Directory Structure
```
docs/
  backend/       api/          database/
  frontend/      devops/       guides/
  architecture/
```

Now apply these rules to: $ARGUMENTS
