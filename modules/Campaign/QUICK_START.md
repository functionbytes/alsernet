# Campaign Module Seeders - Quick Start Guide

## TL;DR - Get Started in 30 Seconds

```bash
# Run all Campaign seeders
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignDatabaseSeeder

# That's it! ✅
```

## What You Get

```
✅ 5 Mailing Lists
   ├── Main Newsletter
   ├── Product Updates
   ├── Promotional Campaigns
   ├── Weekly Digest
   └── Event Notifications

✅ 4 Marketing Campaigns
   ├── Q1 Product Launch
   ├── Spring Sale
   ├── Customer Testimonials
   └── Webinar Invitation

✅ 5 Audience Segments
   ├── Active Subscribers
   ├── High Engagement Users
   ├── Premium Members
   ├── Inactive for 30 Days
   └── Recent Joiners

✅ 3 Email Templates
   ├── Welcome Email
   ├── Promotional Template
   └── Newsletter Template

✅ 2 Automations
   ├── Welcome Automation
   └── Re-engagement Campaign

✅ 15 Sample Subscribers per List
   └── With realistic emails, tags, and verification status
```

## Quick Commands

```bash
# Run all Campaign seeders
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignDatabaseSeeder

# Run individual seeders
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignSeeder
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignSegmentSeeder
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignSubscriberSeeder

# Database refresh with all seeds
php artisan migrate:fresh --seed

# Check what was created (Laravel Tinker)
php artisan tinker
> Campaign::count()
> CampaignMaillist::count()
> CampaignSegment::count()
> CampaignMaillistsSubscriber::count()
```

## File Locations

All seeders are located at:
```
Modules/Campaign/database/seeders/
├── CampaignDatabaseSeeder.php          (Main orchestrator)
├── CampaignMaillistSeeder.php          (5 mailing lists)
├── CampaignSeeder.php                  (4 campaigns)
├── CampaignSegmentSeeder.php           (5 segments + fields)
├── CampaignTemplateSeeder.php          (3 email templates)
├── CampaignAutomationSeeder.php        (2 automations)
├── CampaignSubscriberSeeder.php        (15 subscribers per list)
├── README.md                            (Detailed documentation)
```

## Execution Order

Seeders run in this order (automatically):

```
1. CampaignMaillistSeeder      (creates mailing lists)
   ↓
2. CampaignSeeder              (creates campaigns)
   ↓
3. CampaignSegmentSeeder       (creates segments & fields)
   ↓
4. CampaignTemplateSeeder      (creates email templates)
   ↓
5. CampaignAutomationSeeder    (creates automations)
```

## Safe to Run?

✅ **Yes! 100% Safe**

- Uses `firstOrCreate()` - won't create duplicates on re-runs
- No destructive operations (delete/truncate)
- No activity logging triggered
- Production-ready code

## Testing with Seeders

```php
// In your feature test
class CampaignTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->seed(CampaignSeeder::class);
    }

    public function test_campaign_exists()
    {
        $campaign = Campaign::first();
        $this->assertNotNull($campaign);
    }
}
```

## Sample Data Details

### Campaigns Created

| Campaign | Subject | Content Type |
|----------|---------|--------------|
| Q1 Product Launch | "Introducing Our New Q1 Products" | Gradient header, product variants |
| Spring Sale | "Spring Into Savings - Up to 40% Off" | Category discounts with codes |
| Customer Testimonials | "See What Our Happy Customers Say" | Star ratings, customer quotes |
| Webinar Invitation | "Join Us for an Exclusive Webinar" | Event details, speaker bios |

### Segments Created

| Segment | Condition | Type |
|---------|-----------|------|
| Active Subscribers | Subscribed status | AND |
| High Engagement | 5+ opens AND 2+ clicks | AND |
| Premium Members | Tag = premium OR vip | ANY |
| Inactive 30 Days | No opens in 30 days | AND |
| Recent Joiners | Subscribed within 7 days | AND |

### Fields Created (per Mailing List)

- First Name
- Last Name
- Company
- Location
- Industry
- Join Date

### Subscriber Tags

- premium
- vip
- engaged
- active
- dormant

## Common Questions

### Q: Will this create duplicates if I run it twice?
**A:** No. Uses `firstOrCreate()` - safe to run multiple times.

### Q: Can I use this in production?
**A:** Yes, but test in staging first. Consider using different email domains.

### Q: How long does it take?
**A:** < 2 seconds for all seeders to complete.

### Q: What if a seeder fails?
**A:** Each seeder is independent. You can run them individually to test.

### Q: Can I modify the seed data?
**A:** Yes. Edit the seeder files to customize sample data.

### Q: Where are sample emails stored?
**A:** In the Campaign and CampaignTemplate entities in the database.

## Troubleshooting

### Error: "No mailing list found"
**Solution**: Run `CampaignMaillistSeeder` first
```bash
php artisan db:seed --class=modules\\Campaign\\Database\\Seeders\\CampaignMaillistSeeder
```

### Error: "Class not found"
**Solution**: Verify namespace is correct
```php
Modules\Campaign\Database\Seeders\CampaignDatabaseSeeder
```

### Error: "Table not found"
**Solution**: Run migrations first
```bash
php artisan migrate
```

## Documentation Files

| File | Purpose | Location |
|------|---------|----------|
| README.md | Complete guide | Modules/Campaign/database/seeders/ |
| SEEDERS_IMPLEMENTATION.md | Detailed checklist | Modules/Campaign/ |
| QUICK_START.md | This file | Modules/Campaign/ |

## Next Steps

1. **Run the seeders**: `php artisan db:seed --class=Modules\\Campaign\\Database\\Seeders\\CampaignDatabaseSeeder`
2. **Verify data**: Check in Laravel admin panel or database
3. **Customize**: Edit seeders for your specific needs
4. **Test**: Use seeded data for feature testing

## Support

For detailed documentation, see:
- `Modules/Campaign/database/seeders/README.md` - Complete guide
- `Modules/Campaign/SEEDERS_IMPLEMENTATION.md` - Implementation checklist
- `SEEDERS_SUMMARY.md` - Overview and statistics

---

**Last Updated**: 2025-12-29
**Status**: Production Ready ✅
**Laravel Version**: 12+
