# Helpdesk Database Setup Guide

## Overview

The Helpdesk module uses a separate MySQL/MariaDB database configured as the `helpdesk` connection. This document provides instructions for setting up the helpdesk database and running migrations.

## Prerequisites

- MySQL or MariaDB server running
- Access to create a new database
- Laravel environment configured

## Database Configuration

### Configuration File

The helpdesk connection is configured in `config/database.php`:

```php
'helpdesk' => [
    'driver' => 'mysql',
    'host' => env('HELPDESK_DB_HOST', env('DB_HOST', '127.0.0.1')),
    'port' => env('HELPDESK_DB_PORT', env('DB_PORT', '3306')),
    'database' => env('HELPDESK_DB_DATABASE', 'Alsernet_helpdesk'),
    'username' => env('HELPDESK_DB_USERNAME', env('DB_USERNAME', 'root')),
    'password' => env('HELPDESK_DB_PASSWORD', env('DB_PASSWORD', '')),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]
```

### Environment Variables

You can customize the helpdesk database connection by adding these to your `.env` file:

```env
# Helpdesk Database Configuration
HELPDESK_DB_HOST=127.0.0.1
HELPDESK_DB_PORT=3306
HELPDESK_DB_DATABASE=Alsernet_helpdesk
HELPDESK_DB_USERNAME=root
HELPDESK_DB_PASSWORD=
```

If not specified, it will use the default values from the config above.

## Step 1: Create the Database

### Using MySQL Command Line

```bash
mysql -u root -p
```

```sql
CREATE DATABASE Alsernet_helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### Using MySQLWorkbench

1. Right-click on "Databases" in the left panel
2. Select "Create New Database"
3. Database name: `Alsernet_helpdesk`
4. Character set: `utf8mb4`
5. Collation: `utf8mb4_unicode_ci`
6. Click "Apply"

## Step 2: Run Helpdesk Migrations

The helpdesk migrations are located in `database/migrations/helpdesk/` and create all necessary tables.

### Run All Helpdesk Migrations

```bash
php artisan migrate --database=helpdesk
```

### Run Specific Migration File

```bash
php artisan migrate --database=helpdesk --path=database/migrations/helpdesk/2025_12_05_000001_create_helpdesk_customers_table.php
```

### Verify Migrations

```bash
php artisan migrate:status --database=helpdesk
```

Expected output:
```
Migration name                                  Batch  Status
database/migrations/helpdesk/2025_12_05_000001  1      Ran
database/migrations/helpdesk/2025_12_05_000002  1      Ran
database/migrations/helpdesk/2025_12_05_000003  1      Ran
database/migrations/helpdesk/2025_12_05_000004  1      Ran
database/migrations/helpdesk/2025_12_05_000005  1      Ran
database/migrations/helpdesk/2025_12_05_000006  1      Ran
database/migrations/helpdesk/2025_12_05_000007  1      Ran
database/migrations/helpdesk/2025_12_05_000008  1      Ran
database/migrations/helpdesk/2025_12_05_000009  1      Ran
database/migrations/helpdesk/2025_12_05_000010  1      Ran
database/migrations/helpdesk/2025_12_05_000011  1      Ran
database/migrations/helpdesk/2025_12_05_000012  1      Ran
database/migrations/helpdesk/2025_12_05_000013  1      Ran
database/migrations/helpdesk/2025_12_05_000014  1      Ran
database/migrations/helpdesk/2025_12_05_000015  1      Ran
database/migrations/helpdesk/2025_12_05_000016  1      Ran
```

## Step 3: Seed Initial Data (Optional)

If you want to populate the helpdesk database with initial data (conversation statuses, templates, etc.), run the seeders:

```bash
php artisan db:seed --database=helpdesk
```

Or seed specific classes:

```bash
php artisan db:seed --database=helpdesk --class=HelpdesKConversationStatusSeeder
php artisan db:seed --database=helpdesk --class=HelpdesKCannedReplySeeder
```

## Migration Files

### Created Tables

1. **helpdesk_customers** - Customer information
   - id, name, email, phone, avatar, verification, status, created_at, updated_at

2. **helpdesk_customer_sessions** - Customer browser sessions
   - id, customer_id, ip, user_agent, country, city, browser, created_at

3. **helpdesk_page_visits** - Page visit analytics
   - id, customer_id, page_url, time_spent, scroll_depth, created_at

4. **helpdesk_conversation_statuses** - Ticket/conversation states
   - id, name, color, category, order, created_at

5. **helpdesk_conversations** - Support tickets/conversations
   - id, customer_id, assignee_id, status_id, subject, description, closed_at, archived_at, created_at

6. **helpdesk_conversation_items** - Individual messages
   - id, conversation_id, sender_id, type, body, is_internal, created_at

7. **helpdesk_conversation_reads** - Message read status
   - id, conversation_id, user_id, read_at

8. **helpdesk_canned_replies** - Pre-written message templates
   - id, title, content, category, created_at

9. **helpdesk_campaigns** - Marketing/support campaigns
   - id, name, type, content, status, impressions_count, created_at

10. **helpdesk_campaign_impressions** - Campaign view tracking
    - id, campaign_id, customer_id, viewed_at

11. **helpdesk_campaign_templates** - Campaign message templates
    - id, campaign_id, name, content, position, created_at

12. **helpdesk_ai_agents** - AI agent configurations
    - id, name, description, llm_provider, api_key, model, status, created_at

13. **helpdesk_ai_agent_flows** - AI conversation flows
    - id, agent_id, name, description, structure, status, created_at

14. **helpdesk_ai_agent_flow_nodes** - Flow diagram nodes
    - id, flow_id, node_type, configuration, position, created_at

15. **helpdesk_ai_agent_sessions** - User conversations with AI
    - id, customer_id, agent_id, started_at, ended_at

16. **helpdesk_ai_agent_session_messages** - AI conversation messages
    - id, session_id, role, message, created_at

## Troubleshooting

### Error: "Table 'Alsernet_helpdesk' doesn't exist"

**Solution**: Create the database first
```bash
mysql -u root -p -e "CREATE DATABASE Alsernet_helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Error: "Access denied for user 'root'@'localhost'"

**Solution**: Check database credentials in `.env` or `config/database.php`

```env
HELPDESK_DB_USERNAME=your_db_user
HELPDESK_DB_PASSWORD=your_db_password
```

### Error: "SQLSTATE[HY000] [2002] No such file or directory"

**Solution**: Verify MySQL is running
```bash
# macOS/Linux
sudo systemctl status mysql

# Or restart MySQL
sudo systemctl restart mysql

# Or using Homebrew (macOS)
brew services restart mysql
```

### Migrations Won't Run

**Check migration status first**:
```bash
php artisan migrate:status --database=helpdesk
```

**Reset helpdesk database** (⚠️ deletes all data):
```bash
php artisan migrate:reset --database=helpdesk
php artisan migrate --database=helpdesk
```

**Rollback last batch**:
```bash
php artisan migrate:rollback --database=helpdesk
```

## Verification

### Using Tinker

```bash
php artisan tinker
```

```php
// Check if tables exist
DB::connection('helpdesk')->table('helpdesk_customers')->count();
// Should return 0 (no customers yet)

DB::connection('helpdesk')->table('helpdesk_conversation_statuses')->count();
// Should return 5 (default statuses seeded)

// Exit tinker
exit
```

### Using MySQL CLI

```bash
mysql -u root -p Alsernet_helpdesk

SHOW TABLES;
# Should show 16 helpdesk_* tables

SELECT * FROM helpdesk_conversation_statuses;
# Should show default statuses

EXIT;
```

## Settings Pages Access

After setting up the database, the following settings pages will be fully functional:

- `/warehouse/helpdesk/settings/tickets` - Access conversation statuses from database
- `/warehouse/helpdesk/settings/livechat` - Widget configuration
- `/warehouse/helpdesk/settings/ai` - AI agent provider settings
- `/warehouse/helpdesk/settings/search` - Search engine configuration
- `/warehouse/helpdesk/settings/authentication` - Auth security settings
- `/warehouse/helpdesk/settings/uploading` - File upload configuration
- `/warehouse/helpdesk/settings/email` - SMTP configuration
- `/warehouse/helpdesk/settings/system` - System driver configuration
- `/warehouse/helpdesk/settings/captcha` - CAPTCHA provider settings
- `/warehouse/helpdesk/settings/gdpr` - GDPR compliance settings

## Next Steps

1. [Follow the Helpdesk Settings URLs guide](./HELPDESK_SETTINGS_URLS.md)
2. Test each settings page
3. Verify form submissions save correctly
4. Proceed to Phase 7 - Full Integration & Testing

## References

- [Laravel Migrations Documentation](https://laravel.com/docs/migrations)
- [Laravel Multiple Databases](https://laravel.com/docs/database#using-multiple-database-connections)
- [Alsernet Database Architecture](./docs/database/database-architecture.md)
