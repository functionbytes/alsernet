#!/bin/bash
# Claude Code SessionStart hook - provides repo context
# This runs at the beginning of each Claude Code session

echo "=== Repository Context ==="
echo "Branch: $(git branch --show-current 2>/dev/null || echo 'unknown')"
echo "Last commit: $(git log -1 --oneline 2>/dev/null || echo 'none')"

# Uncommitted changes
DIRTY=$(git status --porcelain 2>/dev/null | wc -l | tr -d ' ')
if [ "$DIRTY" -gt 0 ]; then
  echo "Uncommitted changes: $DIRTY files"
else
  echo "Working tree: clean"
fi
echo ""

# Active modules - list from modules/ directory
echo "=== Active Modules ==="
ls -d modules/*/module.json 2>/dev/null | while read f; do
  dirname "$f" | xargs basename
done | sort | sed 's/^/  - /'
echo ""

# Pending migrations
echo "=== Pending Migrations ==="
php artisan migrate:status --pending 2>/dev/null | head -20 || echo "  (artisan not available)"
echo ""

# Quick health check
echo "=== Environment ==="
echo "PHP: $(php -v 2>/dev/null | head -1 | cut -d' ' -f2)"
echo "Laravel: $(php artisan --version 2>/dev/null | cut -d' ' -f3 || echo 'unknown')"
echo "Node: $(node -v 2>/dev/null || echo 'not installed')"

# Redis check
if redis-cli ping 2>/dev/null | grep -q PONG; then
  echo "Redis: connected"
else
  echo "Redis: NOT running"
fi
