# Query Scopes Implementation Checklist

**Use this checklist to track the implementation of query scopes across the Mailing module.**

---

## Phase 1: Foundation ✅ COMPLETE

- [x] Create all global scope classes
- [x] Create all scope trait classes
- [x] Write comprehensive documentation
- [x] Create quick reference guide
- [x] Create getting started guides
- [x] Review code quality and conventions

**Status**: ✅ All foundation work complete
**Next**: Begin Phase 2 - Model Integration

---

## Phase 2: Model Integration (Week 1-2)

### Campaign Model
- [ ] Add `HasCampaignScopes` trait to Campaign model
- [ ] Add `HasCommonScopes` trait to Campaign model
- [ ] Apply `CustomerScope` global scope
- [ ] Test all campaign scopes work correctly
- [ ] Update Campaign factory for testing
- [ ] Write Campaign scope tests
- [ ] Refactor Campaign controller to use scopes

### Subscriber Model
- [ ] Add `HasSubscriberScopes` trait to Subscriber model
- [ ] Add `HasCommonScopes` trait to Subscriber model
- [ ] Consider `MailListScope` global scope (contextual)
- [ ] Test all subscriber scopes work correctly
- [ ] Update Subscriber factory for testing
- [ ] Write Subscriber scope tests
- [ ] Refactor Subscriber controller to use scopes

### SendingServer Model
- [ ] Add `HasMailingServerScopes` trait to SendingServer model
- [ ] Add `HasCommonScopes` trait to SendingServer model
- [ ] Apply `ActiveScope` global scope
- [ ] Test all server scopes work correctly
- [ ] Update SendingServer factory for testing
- [ ] Write SendingServer scope tests
- [ ] Refactor SendingServer controller to use scopes

### Template Models (Template, EmailTemplate, Layout)
- [ ] Add `HasTemplateScopes` trait to Template model
- [ ] Add `HasTemplateScopes` trait to EmailTemplate model
- [ ] Add `HasTemplateScopes` trait to Layout model
- [ ] Add `HasCommonScopes` trait to all template models
- [ ] Test all template scopes work correctly
- [ ] Write Template scope tests
- [ ] Refactor Template controllers to use scopes

### Log Models (BounceLog, FeedbackLog, ActivityLog)
- [ ] Add `HasLogScopes` trait to BounceLog model
- [ ] Add `HasLogScopes` trait to FeedbackLog model
- [ ] Add `HasLogScopes` trait to ActivityLog model
- [ ] Add `HasCommonScopes` trait to all log models
- [ ] Apply `DateFilterScope(30)` to BounceLog
- [ ] Apply `DateFilterScope(30)` to FeedbackLog
- [ ] Apply `DateFilterScope(90)` to ActivityLog
- [ ] Test all log scopes work correctly
- [ ] Write Log scope tests
- [ ] Refactor Log controllers/services to use scopes

### Other Models
- [ ] Add `HasCommonScopes` to Segment model
- [ ] Add `HasCommonScopes` to Field model
- [ ] Add `HasCommonScopes` to Lists model
- [ ] Add `HasCommonScopes` to MailingGroup model
- [ ] Add `HasCommonScopes` to Automation model
- [ ] Test all common scopes work correctly

---

## Phase 3: Database Optimization (Week 2-3)

### Index Creation - Campaign
- [ ] Create index on `mailing_campaigns.status`
- [ ] Create index on `mailing_campaigns.scheduled_at`
- [ ] Create index on `mailing_campaigns.sent_at`
- [ ] Create index on `mailing_campaigns.user_id`
- [ ] Create index on `mailing_campaigns.list_id`
- [ ] Create composite index on `status, scheduled_at`

### Index Creation - Subscriber
- [ ] Create index on `mailing_subscribers.status`
- [ ] Create index on `mailing_subscribers.email`
- [ ] Create index on `mailing_subscribers.subscribed_at`
- [ ] Create index on `mailing_subscribers.validated_at`
- [ ] Create composite index on `mailrelay_id, last_synced_at`
- [ ] Create composite index on `status, subscribed_at`

### Index Creation - SendingServer
- [ ] Create index on `mailing_sending_servers.status`
- [ ] Create index on `mailing_sending_servers.type`
- [ ] Create index on `mailing_sending_servers.user_id`
- [ ] Create index on `mailing_sending_servers.last_sent_at`
- [ ] Create index on `mailing_sending_servers.last_connection_check_at`
- [ ] Create composite index on `quota_value, emailing_sent_today`

### Index Creation - Logs
- [ ] Create index on `mailing_bounce_logs.created_at`
- [ ] Create index on `mailing_bounce_logs.email`
- [ ] Create index on `mailing_bounce_logs.bounce_type`
- [ ] Create index on `mailing_bounce_logs.subscriber_updated`
- [ ] Create index on `mailing_feedback_logs.created_at`
- [ ] Create index on `mailing_activity_logs.created_at`

### Index Creation - Templates
- [ ] Create index on `mailing_templates.is_active`
- [ ] Create index on `mailing_templates.category`
- [ ] Create index on `mailing_templates.user_id`
- [ ] Create composite index on `is_active, category`

### Index Creation - Other Tables
- [ ] Create index on `mailing_fields.visible`
- [ ] Create index on `mailing_fields.required`
- [ ] Create index on `mailing_fields.custom_order`
- [ ] Create index on `mailing_segments.mail_list_id`

### Performance Testing
- [ ] Run query analyzer on all scoped queries
- [ ] Identify slow queries (>100ms)
- [ ] Add missing indexes
- [ ] Verify N+1 queries eliminated
- [ ] Load test with production data volume

---

## Phase 4: Testing (Week 3)

### Unit Tests - Global Scopes
- [ ] Test ActiveScope filters correctly
- [ ] Test ActiveScope can be bypassed
- [ ] Test CustomerScope filters by user
- [ ] Test CustomerScope `withAllCustomers()` macro
- [ ] Test DateFilterScope filters by date
- [ ] Test DateFilterScope `lastDays()` macro
- [ ] Test MailListScope filters by list
- [ ] Test MailListScope `forList()` macro
- [ ] Test StatusScope excludes statuses
- [ ] Test StatusScope `onlyStatus()` macro

### Unit Tests - Campaign Scopes
- [ ] Test `draft()` scope
- [ ] Test `scheduled()` scope
- [ ] Test `sent()` scope
- [ ] Test `readyToSend()` scope
- [ ] Test `highPerforming()` scope
- [ ] Test scope chaining works
- [ ] Test scopes with eager loading
- [ ] Test scopes with pagination

### Unit Tests - Subscriber Scopes
- [ ] Test `sendable()` scope
- [ ] Test `needsSyncing()` scope
- [ ] Test `inGroup()` scope
- [ ] Test `searchSubscribers()` scope
- [ ] Test scope chaining works

### Unit Tests - Server Scopes
- [ ] Test `availableToSend()` scope
- [ ] Test `quotaExceeded()` scope
- [ ] Test `highBounceRate()` scope
- [ ] Test `orderByCapacity()` scope

### Unit Tests - Log Scopes
- [ ] Test `today()` scope
- [ ] Test `hardBounces()` scope
- [ ] Test `unprocessed()` scope
- [ ] Test date range scopes

### Integration Tests
- [ ] Test scopes work with relationships
- [ ] Test scopes work with eager loading
- [ ] Test scopes work with pagination
- [ ] Test scopes work with soft deletes
- [ ] Test multiple scopes chain correctly

### Performance Tests
- [ ] Benchmark scope queries vs raw queries
- [ ] Verify no performance regression
- [ ] Test with large datasets (10k+ records)
- [ ] Profile memory usage
- [ ] Test concurrent query execution

---

## Phase 5: Controller Refactoring (Week 3-4)

### Campaign Controller
- [ ] Identify all Campaign queries
- [ ] Replace status filters with scopes
- [ ] Replace date filters with scopes
- [ ] Replace search logic with scopes
- [ ] Test all controller methods
- [ ] Update controller tests

### Subscriber Controller
- [ ] Identify all Subscriber queries
- [ ] Replace status filters with scopes
- [ ] Replace group filters with scopes
- [ ] Replace search logic with scopes
- [ ] Test all controller methods
- [ ] Update controller tests

### Server Controller
- [ ] Identify all SendingServer queries
- [ ] Replace status filters with scopes
- [ ] Replace quota checks with scopes
- [ ] Test all controller methods
- [ ] Update controller tests

### Analytics/Reports Controllers
- [ ] Replace date filters with log scopes
- [ ] Replace aggregation queries with scopes
- [ ] Test all report methods
- [ ] Update report tests

### API Controllers
- [ ] Update API endpoints to use scopes
- [ ] Test API responses unchanged
- [ ] Update API documentation
- [ ] Version API if needed

---

## Phase 6: Documentation & Training (Week 4)

### Code Documentation
- [ ] Add PHPDoc to all models using scopes
- [ ] Document scope usage in controller docblocks
- [ ] Add code examples to README files
- [ ] Update API documentation
- [ ] Add inline comments where needed

### Team Documentation
- [ ] Create team training presentation
- [ ] Record video tutorial (optional)
- [ ] Write "Scopes Best Practices" guide
- [ ] Document common pitfalls
- [ ] Create FAQ document

### Knowledge Transfer
- [ ] Schedule team walkthrough session
- [ ] Demo scope usage in real queries
- [ ] Q&A session
- [ ] Code review session
- [ ] Pair programming sessions

---

## Phase 7: Deployment (Week 4-5)

### Pre-Deployment
- [ ] All tests passing
- [ ] All indexes created in staging
- [ ] Performance benchmarks acceptable
- [ ] Documentation complete
- [ ] Team training complete

### Staging Deployment
- [ ] Deploy to staging environment
- [ ] Run migration for indexes
- [ ] Test all features in staging
- [ ] Performance test in staging
- [ ] UAT (User Acceptance Testing)

### Production Preparation
- [ ] Create deployment runbook
- [ ] Plan rollback strategy
- [ ] Schedule maintenance window
- [ ] Notify stakeholders
- [ ] Prepare monitoring alerts

### Production Deployment
- [ ] Deploy scope code to production
- [ ] Run index migrations (during low traffic)
- [ ] Monitor performance metrics
- [ ] Check error logs
- [ ] Verify all features working

### Post-Deployment
- [ ] Monitor performance for 24 hours
- [ ] Check query execution times
- [ ] Verify no errors in logs
- [ ] Collect team feedback
- [ ] Document lessons learned

---

## Phase 8: Monitoring & Optimization (Ongoing)

### Performance Monitoring
- [ ] Set up query time monitoring
- [ ] Track slow query log
- [ ] Monitor database CPU usage
- [ ] Track cache hit rates
- [ ] Set up alerts for slow queries

### Usage Analytics
- [ ] Track most-used scopes
- [ ] Identify unused scopes
- [ ] Monitor scope performance
- [ ] Analyze query patterns
- [ ] Optimize frequently-used scopes

### Continuous Improvement
- [ ] Review scope usage monthly
- [ ] Add new scopes as needed
- [ ] Deprecate unused scopes
- [ ] Optimize slow scopes
- [ ] Update documentation

---

## Success Criteria

Mark complete when:

- [ ] All models have appropriate scope traits
- [ ] All controllers use scopes instead of raw queries
- [ ] All indexes created and optimized
- [ ] Test coverage >= 80% for scopes
- [ ] No performance regressions
- [ ] Team trained and comfortable using scopes
- [ ] Documentation complete and accessible
- [ ] Production deployment successful
- [ ] Post-deployment monitoring shows good health

---

## Quick Stats Tracker

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| Models with scopes | 15+ | 0 | ⏳ |
| Controllers refactored | 10+ | 0 | ⏳ |
| Tests written | 50+ | 0 | ⏳ |
| Indexes created | 30+ | 0 | ⏳ |
| Test coverage | 80%+ | 0% | ⏳ |
| Performance improvement | 0%+ | 0% | ⏳ |
| Documentation pages | 6 | 6 | ✅ |

---

## Notes & Issues

**Use this section to track blockers, issues, and important notes during implementation.**

### Blockers
- None currently

### Issues
- None currently

### Important Notes
- Scopes created: 99+ across 6 traits
- Global scopes: 5 available
- Documentation: 56 pages complete
- Foundation phase: ✅ Complete

---

**Last Updated**: January 29, 2026
**Phase**: Phase 1 Complete, Starting Phase 2
**Overall Status**: 🟢 On Track
