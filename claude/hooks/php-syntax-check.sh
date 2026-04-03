#!/bin/bash
# PostToolUse hook: auto-check PHP syntax after Edit/Write
# Extracts file_path from $TOOL_INPUT JSON and runs php -l

# Extract file_path - try python3 first, fallback to grep/sed
if command -v python3 &>/dev/null; then
  FILE_PATH=$(echo "$TOOL_INPUT" | python3 -c "import sys,json; print(json.load(sys.stdin).get('file_path',''))" 2>/dev/null)
else
  FILE_PATH=$(echo "$TOOL_INPUT" | grep -o '"file_path"[[:space:]]*:[[:space:]]*"[^"]*"' | sed 's/.*"file_path"[[:space:]]*:[[:space:]]*"//;s/"$//' 2>/dev/null)
fi

# Only check .php files
if [[ "$FILE_PATH" == *.php ]] && [ -f "$FILE_PATH" ]; then
  RESULT=$(php -l "$FILE_PATH" 2>&1)
  if [ $? -ne 0 ]; then
    echo "SYNTAX ERROR in $(basename "$FILE_PATH"):"
    echo "$RESULT" | grep -v "^$"
  fi
fi
