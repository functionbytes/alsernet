# Case-Sensitivity Guidelines

## 📋 Overview

This project enforces **case-sensitive naming conventions** to prevent synchronization issues between Git and different operating systems (macOS, Linux, Windows).

## 🔒 Configuration

The project repository is configured with:
```bash
core.ignorecase = false
```

This setting is **project-level** and applies to all developers. It ensures Git respects the case of filenames exactly as they appear in the repository.

## ✅ Naming Conventions

### Directories (folders)

- **Always use lowercase** with hyphens for multi-word names
- ✅ Correct: `modules/`, `config/`, `app/`, `database/`
- ✅ Correct: `api-routes/`, `http-middleware/`, `service-providers/`
- ❌ Wrong: `Modules/`, `Config/`, `App/`, `Database/`
- ❌ Wrong: `ApiRoutes/`, `HttpMiddleware/`, `ServiceProviders/`

### Files

- **Always use lowercase with underscores or lowercase with camelCase** depending on context
- **PHP Classes**: PascalCase (e.g., `UserController.php`, `CreateUserAction.php`)
- **PHP Functions/Config**: snake_case (e.g., `create_user.php`, `app_config.php`)
- **Directories containing files**: lowercase (e.g., `app/`, `database/migrations/`)

## ⚠️ What to Avoid

| Issue | Example | Solution |
|-------|---------|----------|
| Mixed case directories | `Modules/` + `modules/` | Use only `modules/` |
| Uppercase directories | `Auth/`, `Core/` | Rename to `auth/`, `core/` |
| Inconsistent naming | `ApiRoutes/` mixed with `api_routes/` | Choose one and use consistently |

## 🔧 Setup for New Developers

When cloning the repository, verify case-sensitivity is enabled:

```bash
# Verify the setting
git config core.ignorecase
# Should output: false

# If not set, configure it
git config core.ignorecase false
```

## 🛑 Pre-commit Hook

A pre-commit hook automatically checks for case-sensitivity violations before allowing commits:

```bash
.git/hooks/pre-commit
```

If you see this error:
```
❌ ERROR: Case-sensitivity conflict detected!
```

**Solution:**
1. Identify the conflicting files
2. Rename them to lowercase
3. Stage the changes: `git add .`
4. Try committing again

## 🔄 Fixing Existing Case-Sensitivity Issues

If you encounter case-sensitivity conflicts:

### Option 1: Using Git (Recommended)

```bash
# Rename via Git (preserves history)
git mv Modules modules
git commit -m "fix: rename Modules to modules for case-sensitivity"
```

### Option 2: Manual Rename

```bash
# Rename locally
mv Modules modules

# Stage the move
git add .

# Commit
git commit -m "fix: case-sensitivity issue in directory naming"
```

## ✨ Best Practices

- **Review file paths** before committing
- **Use lowercase** for all directory names
- **Be consistent** with the existing codebase
- **Test on multiple systems** if possible (Windows, macOS, Linux)
- **Report issues** if you find case-sensitivity conflicts

## 📚 Related Documentation

- [Laravel Module Structure](/docs/backend/modules/)
- [Git Configuration](/docs/development/git-setup.md)
- [Development Setup](/docs/guides/setup.md)

## ❓ FAQ

**Q: Why is case-sensitivity important?**
A: Different operating systems handle case-sensitivity differently. Linux and macOS treat `Modules` and `modules` as different directories, while Windows treats them the same. This causes sync issues across teams.

**Q: Can I use uppercase for anything?**
A: PHP class names should use PascalCase (e.g., `UserController`), but the **directory paths** containing them must use lowercase.

**Q: What if I already have a mixed-case directory?**
A: The pre-commit hook will catch it. Rename it to lowercase and commit the change.

**Q: Does this affect Windows developers?**
A: No. The `core.ignorecase = false` setting helps Windows developers see the correct casing and prevents them from accidentally creating uppercase directories.
