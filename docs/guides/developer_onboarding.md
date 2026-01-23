# 👨‍💻 Developer Onboarding Guide

Welcome to the Alsernet project! This guide will help you set up your development environment correctly.

## 📋 Prerequisites

- Git (v2.20 or higher)
- PHP 8.4+
- Composer
- Node.js 18+
- Docker (optional, for local PostgreSQL/Redis)

## 🚀 Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/yourorg/alsernet.git
cd alsernet
```

### 2. Configure Git Case-Sensitivity (IMPORTANT ⚠️)

This project enforces **case-sensitive file naming** to prevent sync issues across different operating systems.

Run the setup script:

```bash
chmod +x scripts/setup-case-sensitivity.sh
./scripts/setup-case-sensitivity.sh
```

Or manually configure:

```bash
git config core.ignorecase false
```

**Why?** This prevents the `Modules` vs `modules` duplication issue you might see in VS Code/Visual Studio.

### 3. Install Dependencies

```bash
composer install
npm install
```

### 4. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 6. Build Frontend Assets

```bash
npm run dev  # Development with watch
# or
npm run build  # Production build
```

### 7. Start Development Server

```bash
php artisan serve
# In another terminal:
npm run dev
```

## ⚙️ Project Structure

```
alsernet/
├── modules/              # 📦 All modules (Core, Auth, Backup, etc.)
│   ├── Core/            # Core module
│   ├── Auth/            # Authentication module
│   ├── Supplier/        # Supplier module
│   └── ...
├── app/                 # 📝 Main application code
├── config/              # ⚙️  Configuration files
├── database/            # 🗄️  Migrations and seeders
├── public/              # 🌐 Web root
├── resources/           # 🎨 Views and assets
├── routes/              # 🛣️  Route definitions
├── tests/               # ✅ Test suites
├── docs/                # 📚 Project documentation
└── scripts/             # 🔧 Utility scripts
```

## 📖 Important Documentation

- **[Case-Sensitivity Guidelines](/docs/development/case-sensitivity-guidelines.md)** - How file/directory naming works
- **[Git Setup](/docs/development/git-setup.md)** - Git configuration and workflows
- **[Development Workflow](/docs/guides/workflow.md)** - Daily development practices
- **[Testing Guide](/docs/guides/testing.md)** - How to write and run tests
- **[API Documentation](/docs/api/README.md)** - API endpoints and authentication

## 🔐 Case-Sensitivity Rules (Critical!)

**Never do this:**
```
❌ Modules/         (with capital M)
❌ Config/          (with capital C)
❌ Database/        (with capital D)
```

**Always do this:**
```
✅ modules/         (lowercase)
✅ config/          (lowercase)
✅ database/        (lowercase)
```

A pre-commit hook will prevent committing case-sensitivity violations.

## 💻 Development Workflow

### Creating a New Feature

1. Create a feature branch:
   ```bash
   git checkout -b feature/my-feature
   ```

2. Make your changes (following Laravel conventions)

3. Run tests:
   ```bash
   php artisan test
   ```

4. Format code:
   ```bash
   vendor/bin/pint
   ```

5. Commit with a descriptive message:
   ```bash
   git commit -m "feat: Add my feature with description"
   ```

### Before Pushing

```bash
# Verify tests pass
php artisan test

# Verify code formatting
vendor/bin/pint --test

# Pull latest changes
git pull origin main

# Push your branch
git push origin feature/my-feature
```

## 🧪 Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/UserTest.php

# Run with coverage
php artisan test --coverage
```

## 🛠️ Useful Commands

| Command | Purpose |
|---------|---------|
| `php artisan tinker` | Interactive PHP shell |
| `php artisan migrate:fresh --seed` | Reset database with seeds |
| `php artisan horizon` | Monitor queued jobs |
| `php artisan pulse` | View performance metrics |
| `npm run build` | Build frontend for production |

## 🐛 Troubleshooting

### Issue: "Modules vs modules" folders visible in VS Code

**Solution:** Run the case-sensitivity setup script:
```bash
./scripts/setup-case-sensitivity.sh
```

### Issue: Pre-commit hook rejects my commit

**Solution:** You likely have a case-sensitivity violation. Check the error message and:
1. Rename files/directories to lowercase
2. Stage changes: `git add .`
3. Try committing again

### Issue: PHP/Composer errors

**Solution:** Clear cache and reinstall:
```bash
composer clear-cache
composer install
```

## 👥 Getting Help

- **Slack**: #development channel
- **Documentation**: Check `docs/` folder
- **Issues**: Create a GitHub issue with details

## 📝 Commit Message Format

Follow conventional commits:

```
<type>(<scope>): <subject>

<body>

<footer>
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `chore`

Example:
```
feat(auth): Add two-factor authentication

Added TOTP-based 2FA for user accounts. Users can enable 2FA
in their security settings.

Closes #123
```

## ✅ Checklist Before First Commit

- [ ] Clone repository
- [ ] Run `./scripts/setup-case-sensitivity.sh`
- [ ] Install dependencies (`composer install` + `npm install`)
- [ ] Create `.env` file
- [ ] Generate app key (`php artisan key:generate`)
- [ ] Run migrations (`php artisan migrate`)
- [ ] Verify tests pass (`php artisan test`)
- [ ] Create your feature branch
- [ ] Make awesome code! 🚀

## 🎓 Learning Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Project Architecture](/docs/backend/)
- [API Design Patterns](/docs/api/)
- [Database Schema](/docs/database/)

---

**Welcome aboard! 🎉 If you have questions, don't hesitate to ask in the team chat.**
