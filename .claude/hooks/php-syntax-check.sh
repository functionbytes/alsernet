#!/bin/bash
# PostToolUse hook: auto-check PHP syntax after Edit/Write
# Reads tool input from stdin (JSON) and runs php -l on .php files

INPUT=$(cat)

# Extract file_path from stdin JSON
if command -v python3 &>/dev/null; then
  FILE_PATH=$(echo "$INPUT" | python3 -c "import sys,json; print(json.load(sys.stdin).get('tool_input',{}).get('file_path',''))" 2>/dev/null)
elif command -v jq &>/dev/null; then
  FILE_PATH=$(echo "$INPUT" | jq -r '.tool_input.file_path // empty' 2>/dev/null)
else
  FILE_PATH=$(echo "$INPUT" | grep -o '"file_path"[[:space:]]*:[[:space:]]*"[^"]*"' | sed 's/.*"file_path"[[:space:]]*:[[:space:]]*"//;s/"$//' 2>/dev/null)
fi

# Only check .php files
if [[ "$FILE_PATH" == *.php ]] && [ -f "$FILE_PATH" ]; then
  RESULT=$(php -l "$FILE_PATH" 2>&1)
  if [ $? -ne 0 ]; then
    echo "SYNTAX ERROR in $(basename "$FILE_PATH"):" >&2
    echo "$RESULT" | grep -v "^$" >&2
    exit 2
  fi
fi

exit 0
