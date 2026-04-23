#!/bin/bash
# Kimi CLI SessionStart hook - provides repo context
# Run this at the beginning of each Kimi CLI session

jq -n --arg branch "$(git branch --show-current 2>/dev/null || echo 'unknown')" \
      --arg commit "$(git log -1 --oneline 2>/dev/null || echo 'none')" \
      --arg dirty "$(git status --porcelain 2>/dev/null | wc -l | tr -d ' ')" \
      --arg modules "$(ls -d modules/*/module.json 2>/dev/null | while read f; do dirname "$f" | xargs basename; done | sort | paste -sd, -)" \
      --arg php "$(php -v 2>/dev/null | head -1 | cut -d' ' -f2)" \
      --arg laravel "$(php artisan --version 2>/dev/null | cut -d' ' -f3 || echo 'unknown')" \
      --arg node "$(node -v 2>/dev/null || echo 'not installed')" \
      --arg redis "$(redis-cli ping 2>/dev/null | grep -q PONG && echo 'connected' || echo 'NOT running')" \
'{
  context: {
    branch: $branch,
    last_commit: $commit,
    dirty_files: $dirty,
    modules: $modules,
    php: $php,
    laravel: $laravel,
    node: $node,
    redis: $redis
  }
}'
