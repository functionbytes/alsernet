# Kimi CLI Configuration for Inoqualab

This directory contains Kimi CLI-specific configuration adapted from the `.claude/` setup.

## Why this exists

Kimi Code CLI does not support MCP servers natively (those are specific to Claude Code, Cursor, etc.). This configuration provides the same project context, rules, and agent definitions using Kimi CLI's native capabilities.

## Structure

```
.kimi/
├── AGENTS.md              # Main project configuration (read this first)
├── README.md              # This file
├── agents/                # Agent role definitions
│   ├── backend.md         # Backend developer agent
│   ├── frontend.md        # Frontend developer agent
│   ├── api.md             # API architect agent
│   ├── database.md        # Database architect agent
│   ├── testing.md         # QA/testing agent
│   ├── security.md        # Security audit agent
│   └── plan.md            # Planning/architecture agent
├── rules/                 # File-type specific rules
│   ├── blade-views.md     # Blade template rules
│   ├── controllers.md     # Controller rules
│   ├── form-requests.md   # Form Request rules
│   ├── routes.md          # Route file rules
│   └── migrations.md      # Migration rules
├── hooks/                 # Lifecycle scripts
│   └── session-start.sh   # Provides repo context at session start
└── memory/                # Agent memory / known issues
    └── MEMORY.md          # Project-specific knowledge
```

## How to use

### Starting a session

Run the session start hook to get project context:

```bash
bash .kimi/hooks/session-start.sh
```

### Using agents

When you start working on a task, tell Kimi which agent to use:

```
"@backend: Create a new controller for managing orders"
"@frontend: Build the order listing page with DataGrid"
"@database: Add a migration for order statuses"
"@plan: I need to build a complete order management system"
```

Kimi will adopt the role defined in the corresponding `.kimi/agents/*.md` file.

### MCP Alternatives

Since Kimi CLI doesn't support MCP, use these Shell commands instead:

| What you need | Command |
|--------------|---------|
| Database queries | `php artisan tinker --execute="..."` |
| List routes | `php artisan route:list --name=module` |
| Application info | `php artisan --version` |
| Read logs | `tail -n 50 storage/logs/laravel.log` |
| Database schema | `php artisan db:show` or `SHOW TABLES` via tinker |
| Browser automation | Playwright scripts (already installed) |
| Redis | `redis-cli ping` or `redis-cli KEYS '*'` |
| Format code | `vendor/bin/pint --dirty` |
| Run tests | `php artisan test --compact --filter=...` |

### Available skills

The project has skills in `.claude/skills/` that work with Kimi CLI:

- `/new-module` — Create a complete Laravel module
- `/module-entity` — Add CRUD + API + settings to a module
- `/module-test` — Generate PHPUnit tests
- `/module-doctor` — Diagnose module issues
- `/module-audit` — Security + performance audit
- `/fix-bug` — Structured bug fixing
- `/ui-patterns` — Project UI patterns reference

## Differences from Claude Code setup

| Feature | Claude Code | Kimi CLI |
|---------|-------------|----------|
| MCP servers | Native stdio | Not supported |
| Subagents | Built-in Task tool | Use Agent tool with context |
| Hooks | Auto-execute | Manual execution |
| Skills | Auto-detected | Manual invocation |
| Chrome DevTools MCP | Native | Use Playwright scripts |
| Redis MCP | Native | Use redis-cli |
| Context7 MCP | Native | Use SearchWeb |

## Keeping in sync

When `.claude/` configuration changes significantly, update the corresponding files in `.kimi/`:

1. `CLAUDE.md` → `.kimi/AGENTS.md`
2. `.claude/agents/*.md` → `.kimi/agents/*.md`
3. `.claude/rules/*.md` → `.kimi/rules/*.md`
4. `.claude/agent-memory/*.md` → `.kimi/memory/*.md`
