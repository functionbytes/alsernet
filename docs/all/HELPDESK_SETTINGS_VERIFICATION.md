# Helpdesk Settings Verification Guide

This guide provides step-by-step instructions to verify that all Helpdesk settings pages are functioning correctly.

## Prerequisites

1. **Database Setup Complete**
   ```bash
   php artisan migrate --database=helpdesk
   ```

2. **Routes Cached Cleared**
   ```bash
   php artisan optimize:clear
   ```

3. **User Authenticated**
   - Log in to the manager panel as an admin user

## Settings Pages URL Map

| Setting | Route | URL |
|---------|-------|-----|
| Tickets | `warehouse.helpdesk.settings.tickets` | `/warehouse/helpdesk/settings/tickets` |
| LiveChat | `warehouse.helpdesk.settings.livechat` | `/warehouse/helpdesk/settings/livechat` |
| AI | `warehouse.helpdesk.settings.ai` | `/warehouse/helpdesk/settings/ai` |
| Search | `warehouse.helpdesk.settings.search` | `/warehouse/helpdesk/settings/search` |
| Authentication | `warehouse.helpdesk.settings.authentication` | `/warehouse/helpdesk/settings/authentication` |
| Uploading | `warehouse.helpdesk.settings.uploading` | `/warehouse/helpdesk/settings/uploading` |
| Email | `warehouse.helpdesk.settings.email` | `/warehouse/helpdesk/settings/email` |
| System | `warehouse.helpdesk.settings.system` | `/warehouse/helpdesk/settings/system` |
| CAPTCHA | `warehouse.helpdesk.settings.captcha` | `/warehouse/helpdesk/settings/captcha` |
| GDPR | `warehouse.helpdesk.settings.gdpr` | `/warehouse/helpdesk/settings/gdpr` |

## Test Plan

### Test 1: Page Accessibility

Test each page loads without errors:

```bash
# Using Laravel Tinker
php artisan tinker
```

```php
// Test routes exist
Route::getRoutes()->where('name', 'like', 'warehouse.helpdesk.backups.*')->each(fn($r) => echo $r->name . "\n");

// Should output:
// warehouse.helpdesk.backups.tickets
// warehouse.helpdesk.backups.livechat
// ... 8 more

exit
```

Or simply visit each URL in your browser:
- ✅ https://website.test/warehouse/helpdesk/settings/tickets
- ✅ https://website.test/warehouse/helpdesk/settings/livechat
- ✅ https://website.test/warehouse/helpdesk/settings/ai
- ✅ https://website.test/warehouse/helpdesk/settings/search
- ✅ https://website.test/warehouse/helpdesk/settings/authentication
- ✅ https://website.test/warehouse/helpdesk/settings/uploading
- ✅ https://website.test/warehouse/helpdesk/settings/email
- ✅ https://website.test/warehouse/helpdesk/settings/system
- ✅ https://website.test/warehouse/helpdesk/settings/captcha
- ✅ https://website.test/warehouse/helpdesk/settings/gdpr

### Test 2: Form Submission

For each page, test the form submission:

#### Tickets Settings
1. Navigate to `/warehouse/helpdesk/settings/tickets`
2. Change values:
   - Auto Close Days: 45
   - Toggle "Asignación Automática" ON
3. Click "Guardar Cambios"
4. Verify success message appears
5. Refresh page and verify values are persisted

**Expected Behaviors**:
- ✅ Success message appears with green background
- ✅ Message auto-dismisses after 5 seconds
- ✅ Values persist after page refresh
- ✅ Form validates required fields

#### LiveChat Settings
1. Navigate to `/warehouse/helpdesk/settings/livechat`
2. Change values:
   - Enable Widget: Check/Uncheck
   - Widget Position: Change from one option to another
   - Primary Color: Change using color picker
3. Observe color preview updates in real-time
4. Click "Guardar Cambios"
5. Verify success message

**Expected Behaviors**:
- ✅ Color picker updates preview live
- ✅ Form submits successfully
- ✅ Settings persist

#### AI Settings
1. Navigate to `/warehouse/helpdesk/settings/ai`
2. Select LLM Provider dropdown
3. Observe that API Key field appears/updates
4. Enter a value for API Key
5. Click "Guardar Cambios"

**Expected Behaviors**:
- ✅ Dropdown changes available options
- ✅ API Key field is toggleable (show/hide password)
- ✅ Form validates API Key presence if provider selected

### Test 3: Validation

For each setting, test validation:

#### Numeric Validations
- **Auto Close Days** (Tickets):
  - Try entering 0 → Should show "must be at least 1"
  - Try entering 400 → Should show "may not be greater than 365"
  - Try entering text → Should show validation error

- **Session Timeout** (Authentication):
  - Try entering 4 → Should validate min:5
  - Try entering 1500 → Should validate max:1440

#### URL Validations
- **Email Settings**:
  - Try entering "not-a-url" in Host field → Should fail or warn
  - Try entering invalid port (>65535) → Should fail validation

#### Email Validations
- **Email Settings**:
  - Try entering invalid email in "from_address" → Should fail validation

#### Color Validations
- **CAPTCHA/LiveChat**:
  - Try entering invalid hex color → Should fail validation

### Test 4: Dynamic Form Elements

#### Show/Hide Based on Selection

**Email Settings**:
1. Navigate to `/warehouse/helpdesk/settings/email`
2. Select "SMTP" from Driver dropdown → SMTP config fields appear
3. Select "Sendmail" → SMTP fields disappear, Sendmail info appears
4. Select "Mailgun" → Different fields appear

**CAPTCHA Settings**:
1. Navigate to `/warehouse/helpdesk/settings/captcha`
2. Select "Google reCAPTCHA" → reCAPTCHA config fields appear
3. Select "Cloudflare Turnstile" → reCAPTCHA fields disappear, Turnstile fields appear

**Analytics** (AI or System Settings):
1. Toggle "Habilitar Analytics" ON → Analytics options appear
2. Toggle OFF → Options disappear

### Test 5: Button Functionality

#### Password Toggle Button
1. Navigate to Email Settings
2. Locate "Secret Key" field
3. Click the eye icon button → Password becomes visible text
4. Click again → Password hidden again

#### Test Connection Button (Email)
1. Navigate to Email Settings
2. Enter SMTP credentials
3. Click "Probar Conexión" button
4. Should show loading state briefly
5. Should show success or error message

#### Clear Cache Button (System)
1. Navigate to System Settings
2. Click "Limpiar Cache" button
3. Should show confirmation dialog
4. Should execute and show success message

### Test 6: Cache Persistence

1. Navigate to `/warehouse/helpdesk/settings/tickets`
2. Enter a value: `auto_close_days = 42`
3. Submit form
4. Verify success message
5. Navigate away and back to same page
6. **Verify**: Value is still 42 (persisted in cache)

**Test in Tinker**:
```php
php artisan tinker

// Check if cached value exists
cache()->has('helpdesk.tickets')
// Should return: true

// Get cached values
cache()->get('helpdesk.tickets')
// Should contain: ['auto_close_days' => 42, ...]

exit
```

### Test 7: CSRF Protection

1. Navigate to any settings page
2. Open DevTools → Network tab
3. Submit any form
4. Verify request includes `X-CSRF-TOKEN` header
5. Try to intercept and remove CSRF token from request
6. Resubmit → Should get 419 error (CSRF token mismatch)

### Test 8: Error Handling

#### Missing Database Table (Before Migration)
1. Comment out the try-catch in `ticketsIndex()` method
2. Navigate to Tickets Settings without running migrations
3. Should show database error (table not found)
4. Uncomment try-catch → Should show default statuses

#### Invalid Cache Driver
1. Change `CACHE_DRIVER=invalid` in .env
2. Navigate to any settings page
3. Should show cache error

### Test 9: Mobile Responsiveness

For each settings page, test on different screen sizes:

**Desktop (1920px)**
- ✅ Two-column layout visible
- ✅ Sidebar sticky
- ✅ All controls readable

**Tablet (768px)**
- ✅ Stack to single column
- ✅ Touch targets >= 44px
- ✅ Readable font sizes

**Mobile (375px)**
- ✅ Full-width cards
- ✅ Buttons stackable
- ✅ Modals full screen

### Test 10: Accessibility

For each page, test with keyboard navigation:

1. Press TAB key repeatedly
2. Verify focus ring is visible on all buttons/inputs
3. Verify focus order makes sense
4. Press ENTER on buttons → Should activate
5. Press SPACE on checkboxes → Should toggle

### Test Checklist

```
Pages Load Successfully
  ☐ Tickets
  ☐ LiveChat
  ☐ AI
  ☐ Search
  ☐ Authentication
  ☐ Uploading
  ☐ Email
  ☐ System
  ☐ CAPTCHA
  ☐ GDPR

Form Submission Works
  ☐ Tickets
  ☐ LiveChat
  ☐ AI
  ☐ Search
  ☐ Authentication
  ☐ Uploading
  ☐ Email
  ☐ System
  ☐ CAPTCHA
  ☐ GDPR

Success Messages Display
  ☐ All 10 settings pages show success message

Values Persist After Refresh
  ☐ Tickets
  ☐ LiveChat
  ☐ AI
  ☐ Search
  ☐ Authentication
  ☐ Uploading
  ☐ Email
  ☐ System
  ☐ CAPTCHA
  ☐ GDPR

Validation Works
  ☐ Numeric ranges
  ☐ Required fields
  ☐ Email validation
  ☐ URL validation
  ☐ Color validation

Dynamic Elements
  ☐ Show/hide based on selection
  ☐ Password toggles work
  ☐ Color pickers update live
  ☐ Sliders update dynamically

CSRF Protection
  ☐ Token included in forms
  ☐ Invalid tokens rejected

Responsive Design
  ☐ Works on mobile (375px)
  ☐ Works on tablet (768px)
  ☐ Works on desktop (1920px)

Accessibility
  ☐ Keyboard navigation works
  ☐ Focus indicators visible
  ☐ Form labels associated with inputs
  ☐ Error messages clearly displayed
```

## Troubleshooting

### "Route not defined" Error

**Cause**: Routes not registered or route cache stale

**Solution**:
```bash
php artisan route:clear
php artisan optimize:clear
php artisan route:list | grep warehouse.helpdesk.backups
```

### "Table doesn't exist" Error

**Cause**: Helpdesk database migrations not run

**Solution**:
```bash
# Check if helpdesk database exists
mysql -u root -p -e "SHOW DATABASES LIKE 'Alsernet_helpdesk';"

# Run migrations
php artisan migrate --database=helpdesk

# Verify
php artisan migrate:status --database=helpdesk
```

### Form Not Submitting

**Causes**:
1. CSRF token missing or invalid
2. Invalid form data
3. Validation error (check browser console)

**Solution**:
```bash
# Check CSRF token in HTML
curl https://website.test/warehouse/helpdesk/settings/tickets | grep _token

# Check server logs
tail -f storage/logs/laravel.log
```

### Settings Not Persisting

**Cause**: Cache driver misconfigured or not working

**Solution**:
```php
php artisan tinker

// Check cache driver
config('cache.default')  // Should be 'redis' or 'file'

// Test cache manually
cache()->put('test', 'value', 3600)
cache()->get('test')  // Should return 'value'

exit
```

## Performance Testing

### Page Load Time

Test page load times with browser DevTools:

```javascript
// In browser console
performance.measure('page-load', 'navigationStart', 'loadEventEnd');
performance.getEntriesByType('measure').forEach(m => console.log(m.name, m.duration + 'ms'));
```

**Expected**: < 500ms for initial page load

### Form Submission Time

```javascript
// Measure form submission
const start = performance.now();
document.querySelector('form').submit();
// In server logs, measure response time
// Expected: < 300ms
```

## Final Verification

Once all tests pass, run the complete test suite:

```bash
php artisan test --filter=Helpdesk
```

Or if using PHPUnit:

```bash
./vendor/bin/phpunit tests/Feature/Helpdesk/
```

## Sign-off

- [ ] All 10 settings pages accessible
- [ ] All forms submit successfully
- [ ] All validation rules working
- [ ] All data persists in cache
- [ ] Mobile responsive
- [ ] Keyboard accessible
- [ ] CSRF protected
- [ ] Error handling robust
- [ ] Performance acceptable
- [ ] No console errors

**Date Verified**: ________
**Verified By**: ________

## Next Steps

Once verification is complete:

1. ✅ Phase 6 is ready for production
2. 📋 Begin Phase 7 - Full Integration & Testing
3. 🚀 Proceed to deployment

See [Phase 7 Plan](./migration/FASE-7-INTEGRACION-TESTING.md) for next steps.
