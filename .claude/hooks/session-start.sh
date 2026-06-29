#!/bin/bash
# Claude Code SessionStart hook - provides repo context
# This runs at the beginning of each Claude Code session

jq -n --arg branch "$(git branch --show-current 2>/dev/null || echo 'unknown')" \
      --arg commit "$(git log -1 --oneline 2>/dev/null || echo 'none')" \
      --arg dirty "$(git status --porcelain 2>/dev/null | wc -l | tr -d ' ')" \
      --arg modules "$(ls -d modules/*/module.json 2>/dev/null | while read f; do dirname "$f" | xargs basename; done | sort | paste -sd, -)" \
      --arg php "$(php -v 2>/dev/null | head -1 | cut -d' ' -f2)" \
      --arg laravel "$(php artisan --version 2>/dev/null | cut -d' ' -f3 || echo 'unknown')" \
      --arg node "$(node -v 2>/dev/null || echo 'not installed')" \
      --arg redis "$(redis-cli ping 2>/dev/null | grep -q PONG && echo 'connected' || echo 'NOT running')" \
'{
  hookSpecificOutput: {
    hookEventName: "SessionStart",
    additionalContext: "Branch: \($branch) | Last commit: \($commit) | Dirty files: \($dirty) | Modules: \($modules) | PHP: \($php) | Laravel: \($laravel) | Node: \($node) | Redis: \($redis)"
  }
}'
