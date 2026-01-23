# Document Upload System - Production Deployment & Verification

## Status: Configuration Files Ready for Deployment

The following configuration files have been created to fix PHP upload limits on the production server:

- **`.htaccess`** - Apache PHP configuration (35 lines)
- **`.user.ini`** - PHP-FPM configuration (5 lines)

**Current Limits** (Production Server):
- `upload_max_filesize`: 2M ❌ (needs 50M)
- `post_max_size`: 8M ❌ (needs 50M)
- `memory_limit`: Default ❌ (needs 256M)
- `max_execution_time`: Default ❌ (needs 300s)

**Target Server**: `webadminpruebas.a-alvarez.com`
**Target Path**: `/home2/webadminpruebas/web/`

---

## Deployment Steps

### Step 1: Deploy Configuration Files
```bash
scp .htaccess .user.ini webadminpruebas@webadminpruebas.a-alvarez.com:/home2/webadminpruebas/web/
```

### Step 2: Verify Deployment on Production Server
```bash
ssh webadminpruebas@webadminpruebas.a-alvarez.com
php -i | grep -E "upload_max_filesize|post_max_size|memory_limit|max_execution_time"
ls -la /home2/webadminpruebas/web/.user.ini
cat /home2/webadminpruebas/web/.user.ini
```

**Expected Output**:
```
upload_max_filesize => 50M => 50M
post_max_size => 50M => 50M
memory_limit => 256M => 256M
max_execution_time => 300 => 300
```

### Step 3: Restart PHP-FPM (if needed)
```bash
sudo systemctl restart php-fpm
# or
sudo service php-fpm restart
```

---

## Testing Workflow

After deployment, execute these tests in order:

### Test 1: Single Small File Upload (< 1MB)
**Goal**: Verify basic upload works
- Upload DNI frontal (small PDF)
- Check response: `status: "success"`
- Verify in PrestaShop UI: File appears with download/delete buttons

### Test 2: Medium File Upload (5-10MB)
**Goal**: Verify size limits increased
- Upload larger document (8MB)
- Should succeed without `PostTooLargeException`
- File should save to MediaLibrary

### Test 3: Complete Workflow (Both Documents)
**Goal**: Verify full end-to-end process
1. Upload `dni_frontal` → Counter shows "1/2 cargados"
2. Upload `dni_trasera` → Counter shows "2/2 cargados"
3. Success message appears
4. Form shows completion screen
5. Check email queue: `SendDocumentUploadedConfirmationJob` should be queued

### Test 4: Verify Database Persistence
**Goal**: Confirm files saved to MediaLibrary
```bash
ssh webadminpruebas@webadminpruebas.a-alvarez.com
cd /home2/webadminpruebas/web
php artisan tinker
>>> $doc = Document::uid('68db039b13f4e')->first();
>>> $doc->media->count();  // Should be 2 (dni_frontal + dni_trasera)
>>> $doc->getUploadedDocumentsWithDetails();  // Should show both files
```

### Test 5: Verify Email Delivery
**Goal**: Ensure email sent only when all documents complete
1. Complete workflow (all docs uploaded)
2. Check email queue processed: `php artisan queue:failed`
3. Verify email sent to user

---

## Rollback Plan

If issues occur:

1. **Revert configuration files**:
   ```bash
   ssh webadminpruebas@webadminpruebas.a-alvarez.com
   rm /home2/webadminpruebas/web/.htaccess /home2/webadminpruebas/web/.user.ini
   ```

2. **Check error logs**:
   ```bash
   tail -50 /home2/webadminpruebas/web/storage/logs/laravel.log
   ```

---

## Key Files Modified

**Backend**:
- `app/Http/Controllers/Api/DocumentsController.php` - Fixed response structure, added file validation
- `app/Models/Order/Order.php` - Fixed `validateDniDocuments()` method
- `integrations/prestashop/content/override/classes/order/Order.php` - API response parsing

**Frontend**:
- `integrations/prestashop/content/modules/alsernetforms/views/templates/hook/forms/documents/gun.tpl` - Updated HTML structure
- `integrations/prestashop/content/modules/alsernetforms/views/css/front/form.css` - Complete CSS rewrite
- `integrations/prestashop/content/modules/alsernetforms/views/js/front/documents.js` - Sequential upload, validation

**Configuration**:
- `.htaccess` - Apache PHP limits
- `.user.ini` - PHP-FPM limits

---

## Known Issues & Solutions

| Issue | Root Cause | Solution |
|-------|-----------|----------|
| `PostTooLargeException` | PHP limits too low | Deploy .htaccess/.user.ini |
| Empty `uploaded_documents` | Files not saving to MediaLibrary | Increase PHP limits + refresh model |
| Email sent on first upload | Event triggered each upload | Wrapped in `hasAllRequiredDocuments()` check |
| Counter not updating | UI not calling `loadDocumentStatus()` | Sequential upload calls refresh |
| Response structure mismatch | API returning array instead of object | Changed to `getUploadedDocumentsWithDetails()` |

---

## Current Git Status

Untracked files (configuration):
- `.htaccess` ✅ Ready for deployment
- `.user.ini` ✅ Ready for deployment

Modified files:
- `app/Http/Controllers/Api/DocumentsController.php`
- `app/Models/Order/Order.php`
- PrestaShop template and JavaScript files

Ready to commit: All fixes complete pending production deployment verification
