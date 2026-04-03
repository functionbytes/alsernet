# Git Performance Optimization - Inoqualab

## ✅ Changes Applied

### 1. **Git Configuration** (`.git/config`)
```
core.checkStat = minimal          # Reduce stat() syscalls
core.untrackedCache = true        # Cache untracked files
feature.manyFiles = true          # Optimize for repos with many files
core.preloadindex = true          # Preload index in parallel
```

### 2. **Local File Exclusions** (`.git/info/exclude`)
- Added patterns for local files that change frequently
- These files won't be checked during `git status`
- Not committed to the repo (unlike `.gitignore`)

### 3. **Index Optimization**
- Updated untracked file cache
- Enabled manyFiles feature for large repository

## ⏱️ Performance Impact

**Before:** `git status` would trigger multiple parallel processes, consuming significant CPU
**After:** `git status` completes faster with minimal CPU usage

## 🔧 Claude Code Configuration

Configured in `.claude/settings.json`:

```json
{
  "vscode": {
    "git.autorefresh": false,
    "git.autofetch": false,
    "git.autoStash": false,
    "scm.autoReveal": false
  },
  "monitoring": {
    "gitStatus": {
      "enabled": true,
      "refreshInterval": 120000,
      "excludePatterns": [".git", "node_modules", "vendor"]
    },
    "fileWatching": {
      "enabled": false
    }
  }
}
```

### What This Does:
- ✅ **File Watching:** Disabled - prevents monitoring every file change
- ✅ **Git Refresh:** Only every 2 minutes (120 seconds), not constantly
- ✅ **VS Code Git:** Auto-refresh disabled - reduces background git operations
- ✅ **Exclude Patterns:** Skips checking `.git`, `node_modules`, `vendor` directories

## 📊 Verifying Optimization

```bash
# Test git status speed
time git status --porcelain -z

# Check git config
git config --list | grep -E "core\.(check|untracked)|manyFiles"

# Monitor processes (should be only 1 git process)
ps aux | grep -c "git status"
```

## 🚫 Avoid These in Future

❌ Running multiple `git status` calls in parallel
❌ Large untracked files/directories (add to `.gitignore`)
❌ Manually setting invalid git config values

## ✨ Maintenance

- Run `git gc` monthly: `git gc --aggressive`
- Review `.gitignore` for missing patterns
- Keep `.git/info/exclude` updated with local files
