# Query Scopes Documentation Index

**Navigation guide for all scope-related documentation in the Mailing module**

---

## 📚 Documentation Files

### Main Documentation

| Document | Description | Location |
|----------|-------------|----------|
| **Migration Report** | Complete migration documentation with 100+ scopes | `/docs/SCOPES_MIGRATION_REPORT.md` |
| **Quick Reference** | Fast lookup for all scope methods | `/docs/SCOPES_QUICK_REFERENCE.md` |
| **Global Scopes README** | How to use global scopes | `/app/Models/Scopes/README.md` |
| **Traits README** | How to use scope traits | `/app/Traits/README.md` |

---

## 🗂️ Source Code Locations

### Global Scopes (5 files)
```
app/Models/Scopes/
├── ActiveScope.php          - Filter active records
├── CustomerScope.php        - Multi-tenant filtering
├── DateFilterScope.php      - Recent records only
├── MailListScope.php        - Filter by mailing list
└── StatusScope.php          - Exclude specific statuses
```

### Scope Traits (6 files)
```
app/Traits/
├── HasCommonScopes.php          - 17 generic scopes
├── HasCampaignScopes.php        - 19 campaign scopes
├── HasSubscriberScopes.php      - 20 subscriber scopes
├── HasMailingServerScopes.php   - 14 server scopes
├── HasTemplateScopes.php        - 11 template scopes
└── HasLogScopes.php             - 18 log scopes
```

---

## 🎯 Quick Links by Use Case

### I want to...

**Learn about scopes from scratch**
→ Start with `/app/Models/Scopes/README.md`
→ Then read `/app/Traits/README.md`

**Find a specific scope quickly**
→ Use `/docs/SCOPES_QUICK_REFERENCE.md`

**Understand the full migration**
→ Read `/docs/SCOPES_MIGRATION_REPORT.md`

**Add scopes to a model**
→ See examples in `/docs/SCOPES_MIGRATION_REPORT.md` section 4

**Create a new scope**
→ Follow guides in `/app/Models/Scopes/README.md` or `/app/Traits/README.md`

**Test scopes**
→ See testing section in `/docs/SCOPES_MIGRATION_REPORT.md` section 5

**Optimize scope performance**
→ Read performance section in `/docs/SCOPES_MIGRATION_REPORT.md` section 6

---

## 📊 Scope Statistics

- **Global Scopes**: 5
- **Scope Traits**: 6
- **Total Local Scopes**: 99+
- **Models Analyzed**: 50+
- **Documentation Pages**: 6

---

## 🚀 Getting Started (5 Minutes)

### Step 1: Add Scopes to a Model (2 min)
```php
use Modules\Mailing\Traits\HasCommonScopes;
use Modules\Mailing\Traits\HasCampaignScopes;

class Campaign extends Model
{
    use HasCommonScopes, HasCampaignScopes;
}
```

### Step 2: Use Scopes in Queries (2 min)
```php
// Simple query
$drafts = Campaign::draft()->get();

// Chained scopes
$recent = Campaign::draft()
    ->recent(30)
    ->search('Newsletter')
    ->get();
```

### Step 3: Test Your Scopes (1 min)
```php
public function test_draft_scope_works()
{
    Campaign::factory()->create(['status' => CampaignStatus::DRAFT]);
    $this->assertCount(1, Campaign::draft()->get());
}
```

**Done!** You're now using query scopes.

---

## 🔍 Scope Finder

### By Model Type

| Model | Recommended Traits |
|-------|-------------------|
| Campaign | `HasCampaignScopes`, `HasCommonScopes` |
| Subscriber | `HasSubscriberScopes`, `HasCommonScopes` |
| SendingServer | `HasMailingServerScopes`, `HasCommonScopes` |
| Template | `HasTemplateScopes`, `HasCommonScopes` |
| EmailTemplate | `HasTemplateScopes`, `HasCommonScopes` |
| BounceLog | `HasLogScopes`, `HasCommonScopes` |
| FeedbackLog | `HasLogScopes`, `HasCommonScopes` |
| ActivityLog | `HasLogScopes`, `HasCommonScopes` |

### By Use Case

| I need to... | Use this scope... | From trait... |
|--------------|-------------------|---------------|
| Filter by status | `byStatus('active')` | HasCommonScopes |
| Get recent records | `recent(30)` | HasCommonScopes |
| Search by text | `search('term')` | HasCommonScopes |
| Get draft campaigns | `draft()` | HasCampaignScopes |
| Get sent campaigns | `sent()` | HasCampaignScopes |
| Find sendable subscribers | `sendable()` | HasSubscriberScopes |
| Check if needs sync | `needsSyncing()` | HasSubscriberScopes |
| Get available servers | `availableToSend()` | HasMailingServerScopes |
| Check server quota | `quotaExceeded()` | HasMailingServerScopes |
| Get today's logs | `today()` | HasLogScopes |
| Filter hard bounces | `hardBounces()` | HasLogScopes |

---

## 📖 Documentation Reading Order

### For Developers (New to Scopes)
1. Global Scopes README (`/app/Models/Scopes/README.md`)
2. Traits README (`/app/Traits/README.md`)
3. Quick Reference (`/docs/SCOPES_QUICK_REFERENCE.md`)
4. Migration Report - Sections 4-8 (`/docs/SCOPES_MIGRATION_REPORT.md`)

### For Architects (Understanding System)
1. Migration Report - Full read (`/docs/SCOPES_MIGRATION_REPORT.md`)
2. Quick Reference for lookup (`/docs/SCOPES_QUICK_REFERENCE.md`)
3. Source code review (`/app/Models/Scopes/` and `/app/Traits/`)

### For Maintainers (Adding Features)
1. Quick Reference - Find existing scopes (`/docs/SCOPES_QUICK_REFERENCE.md`)
2. Global Scopes README - Adding new global scopes (`/app/Models/Scopes/README.md`)
3. Traits README - Adding new local scopes (`/app/Traits/README.md`)
4. Migration Report - Section 9 (Future Enhancements) (`/docs/SCOPES_MIGRATION_REPORT.md`)

---

## 🛠️ Common Tasks

### Task: Add a New Global Scope
1. Create file in `/app/Models/Scopes/`
2. Implement `Scope` interface
3. Add to model's `booted()` method
4. Test with `withoutGlobalScope()`
5. Document in relevant README

**See**: `/app/Models/Scopes/README.md` - "Adding a New Global Scope"

### Task: Add a New Local Scope
1. Find appropriate trait or create new one in `/app/Traits/`
2. Add `scopeMethodName()` method
3. Add trait to model
4. Test in queries
5. Update Quick Reference

**See**: `/app/Traits/README.md` - "Creating Custom Scope Traits"

### Task: Optimize Scope Performance
1. Enable query log: `DB::enableQueryLog()`
2. Run scope query
3. Review SQL: `dd(DB::getQueryLog())`
4. Add database indexes for scoped columns
5. Use eager loading where needed

**See**: `/docs/SCOPES_MIGRATION_REPORT.md` - Section 6 "Performance Considerations"

---

## 📞 Support Resources

### Internal Documentation
- Migration Report: Complete scope reference
- Quick Reference: Fast scope lookup
- READMEs: How-to guides

### Laravel Documentation
- [Query Scopes](https://laravel.com/docs/12.x/eloquent#query-scopes)
- [Global Scopes](https://laravel.com/docs/12.x/eloquent#global-scopes)
- [Local Scopes](https://laravel.com/docs/12.x/eloquent#local-scopes)

### Acelle Mail Reference
- Acelle scope patterns documented in Migration Report
- Common Acelle use cases covered in scope traits

---

## 🔄 Updates and Versioning

**Current Version**: 1.0.0
**Last Updated**: January 29, 2026
**Generated By**: Claude Sonnet 4.5 (Mailing Agent)

### Changelog
- **v1.0.0** (2026-01-29): Initial scope migration complete
  - 5 global scopes created
  - 6 scope traits with 99+ local scopes
  - Complete documentation suite
  - Ready for production use

---

## 📝 Contributing

When adding new scopes:

1. ✅ Choose appropriate location (global vs local)
2. ✅ Follow naming conventions
3. ✅ Write clear docblocks
4. ✅ Add usage examples
5. ✅ Write tests
6. ✅ Update documentation
7. ✅ Consider performance impact

**See**: Migration Report - Section 11 "Conclusion" for guidelines

---

**Need Help?**
- Check Quick Reference for existing scopes
- Read relevant README for how-to guides
- Review Migration Report for detailed examples
- Look at source code for implementation patterns

---

**All documentation is located in**:
- `/modules/Mailing/docs/` - Main documentation
- `/modules/Mailing/app/Models/Scopes/` - Global scopes + README
- `/modules/Mailing/app/Traits/` - Scope traits + README
