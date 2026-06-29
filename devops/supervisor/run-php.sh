#!/bin/bash
# Wrapper to invoke Herd's php84 from supervisor (path has spaces, supervisor's
# default tokenizer cannot handle spaces in command=...).
# Usage: run-php.sh <artisan args...>
exec "/Users/developerts/Library/Application Support/Herd/bin/php84" "/Users/developerts/Herd/system/artisan" "$@"
