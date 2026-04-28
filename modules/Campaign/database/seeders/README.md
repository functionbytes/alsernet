# Campaign Module Database Seeders

This directory contains database seeders for the Campaign module. These seeders create sample data for testing and development purposes.

## Overview

The Campaign module seeders create realistic sample data across the following components:

- **Mailing Lists**: Email list configurations
- **Campaigns**: Marketing email templates with tracking
- **Segments**: Audience filtering and segmentation
- **Subscribers**: Email subscribers with verification status
- **Automations**: Automated email workflows and triggers
- **Email Templates**: Pre-built email designs

## Seeder Execution Order

The main seeder `CampaignDatabaseSeeder` orchestrates running all seeders in the correct dependency order:

```
1. CampaignMaillistSeeder      → Creates mailing lists
2. CampaignSeeder              → Creates marketing campaigns
3. CampaignSegmentSeeder       → Creates segments with conditions
4. CampaignTemplateSeeder      → Creates email templates
5. CampaignAutomationSeeder    → Creates automation workflows
6. CampaignSubscriberSeeder    → Creates subscribers (optional)
```

## Seeder Descriptions

### CampaignMaillistSeeder

Creates 5 sample mailing lists with different configurations:

- **Main Newsletter** - General purpose newsletter list
- **Product Updates** - Product-focused communications
- **Promotional Campaigns** - Sales and promotional content
- **Weekly Digest** - Curated content distribution
- **Event Notifications** - Event and webinar announcements

**Key Fields:**
- `uid` - Unique identifier (ULID)
- `title` - Mailing list name
- `from_email` & `from_name` - Sender information
- `send_welcome_email` - Send confirmation emails
- `subscribe_confirmation` - Require double-opt-in

### CampaignSeeder

Creates 4 comprehensive marketing campaigns:

- **Q1 Product Launch Campaign** - New product announcement
- **Spring Sale Campaign** - Seasonal promotional campaign
- **Customer Testimonial Campaign** - Social proof campaign
- **Webinar Invitation Campaign** - Educational content

**Features:**
- Complete HTML and plain text versions
- Professional email templates
- Tracking enabled (opens and clicks)
- Ready-to-use email content
- Associated with mailing lists

### CampaignSegmentSeeder

Creates 5 audience segments with filtering conditions:

- **Active Subscribers** - Subscribed status only
- **High Engagement Users** - Multiple opens and clicks
- **Premium Members** - VIP and premium tags
- **Inactive for 30 Days** - No email opens in 30 days
- **Recent Joiners** - Subscribed within last 7 days

**Additional:**
- Creates 6 custom fields (First Name, Last Name, Company, Location, Industry, Join Date)
- Supports various operators (subscribed, opened_email, has_tag, etc.)
- Uses AND/ANY matching for flexible filtering

### CampaignTemplateSeeder

Creates 3 email template designs:

- **Welcome Email Template** - New subscriber welcome
- **Promotional Campaign Template** - Sales and offers
- **Newsletter Template** - Regular content distribution

**Includes:**
- Professional HTML layouts
- Plain text versions
- Preview text (preheader)
- Tracking enabled
- Inline CSS for email client compatibility

### CampaignAutomationSeeder

Creates 2 automation workflows:

- **Welcome Automation** - Sends welcome email immediately, then follow-up after 3 days
- **Re-engagement Campaign** - Targets inactive subscribers with re-engagement emails and unsubscribe dormant users

**Features:**
- JSON-encoded workflow data
- Multiple automation elements (trigger, send, wait, action)
- Configurable delays and timing
- Timezone support (UTC by default)

### CampaignSubscriberSeeder

Creates 15 sample subscribers across all mailing lists:

- Realistic email addresses with name extraction
- Verification status variation (verified/unverified)
- Random tag assignment for segmentation
- Proper relationship to mailing lists

**Subscriber Data:**
- 15 unique email addresses
- Automatic first/last name extraction
- Random verification states
- Tags: premium, vip, engaged, active, dormant

## Running the Seeders

### Run All Campaign Seeders

```bash
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignDatabaseSeeder
```

### Run Individual Seeders

```bash
# Just mailing lists
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignMaillistSeeder

# Just campaigns
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignSeeder

# Just segments
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignSegmentSeeder

# Just templates
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignTemplateSeeder

# Just automations
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignAutomationSeeder

# Just subscribers
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignSubscriberSeeder
```

### Refresh Database with Seeders

```bash
# Run all migrations and seed the database
php artisan migrate:refresh --seed

# Or target specific module
php artisan migrate:refresh --path=modules/Campaign/database/migrations --seed
```

## Data Safety

### Key Design Decisions

1. **FirstOrCreate Pattern**: All seeders use `firstOrCreate()` to prevent duplicate data on re-runs
2. **ULID Generation**: All UIDs are generated using Laravel's `Str::ulid()` for better performance than UUIDs
3. **No Activity Logging**: Seeders create data silently without triggering activity logs
4. **Realistic Sample Data**: Uses realistic company names, email formats, and industry terms
5. **Idempotent**: Safe to run multiple times without side effects

### Preventing Data Loss

- Seeders only create records if they don't already exist
- No destructive operations (delete/truncate) in seeders
- Safe to run against populated databases
- Use `--force` flag to seed production (not recommended)

## Testing with Seeders

### Typical Test Setup

```php
// In your feature test
public function setUp(): void
{
    parent::setUp();

    // Seed only the data you need for this test
    $this->seed([
        CampaignMaillistSeeder::class,
        CampaignSeeder::class,
    ]);
}

public function test_campaign_can_be_sent()
{
    $campaign = Campaign::first();

    // Test campaign functionality
    $this->assertNotNull($campaign);
}
```

### Using Factories with Seeders

For more flexible test data, combine seeders with factories:

```php
// Create seeders first for baseline data
$this->seed(CampaignMaillistSeeder::class);

// Then use factories for variations
$campaign = Campaign::factory()
    ->for(CampaignMaillist::first())
    ->create();
```

## Customization

### Extending Seeders

To add your own seed data, create a new seeder:

```php
php artisan make:seeder CampaignCustomSeeder --path=Modules/Campaign/database/seeders
```

Then add it to `CampaignDatabaseSeeder`:

```php
public function run(): void
{
    $this->call(CampaignMaillistSeeder::class);
    $this->call(CampaignSeeder::class);
    $this->call(CampaignCustomSeeder::class);  // Your custom seeder
    // ... other seeders
}
```

### Modifying Sample Data

Edit seeder files to customize:

- Email addresses and domains
- Campaign content and templates
- Segment conditions and rules
- Automation workflows
- Subscriber information

## Troubleshooting

### Issue: "No mailing list found" Warning

**Cause**: CampaignMaillistSeeder hasn't been run first

**Solution**: Run seeders in order or run `CampaignDatabaseSeeder`

```bash
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignDatabaseSeeder
```

### Issue: Duplicate Records After Re-run

**Normal Behavior**: Seeders use `firstOrCreate()`, so re-running won't create duplicates

To force fresh data:
```bash
php artisan migrate:refresh --seed
```

### Issue: Foreign Key Constraint Errors

**Cause**: Running seeders out of order or with missing dependencies

**Solution**: Always run `CampaignDatabaseSeeder` which handles dependencies

### Issue: Large Email Templates Truncated

**Solution**: Email content uses heredoc syntax for clean formatting. Verify database column size is sufficient:

```sql
ALTER TABLE campaigns MODIFY COLUMN template_source LONGTEXT;
```

## Performance Considerations

- Seeders typically complete in < 1 second
- Uses bulk operations where possible
- Minimal database queries due to `firstOrCreate()` pattern
- Safe for CI/CD pipelines

## Related Files

- Migration files: `Modules/Campaign/database/migrations/`
- Models: `Modules/Campaign/app/Entities/`
- Controllers: `Modules/Campaign/app/Http/Controllers/`
- Tests: `tests/Feature/Campaign/`

## Contributing

When adding new seeders:

1. Follow the existing naming convention (`*Seeder.php`)
2. Use the `HasUid` trait for all models
3. Implement `firstOrCreate()` for idempotency
4. Add descriptive docblock comments
5. Update this README with seeder details
6. Add the seeder to `CampaignDatabaseSeeder`

## Support

For issues or questions about the Campaign module seeders, refer to:

- Module documentation: `Modules/Campaign/README.md`
- Laravel documentation: https://laravel.com/docs/database#running-seeders
- Context7 documentation for latest package versions
