# Document Upload API - Test Cases

## API Endpoint: `POST /api/documents`

Base URL (Dev): `https://manager.test/api/documents`
Base URL (Prod): `https://webadminpruebas.a-alvarez.com/api/documents`

---

## Test Case 1: Validate Documents Status

**Purpose**: Check which documents are required and which are uploaded

**Request**:
```json
{
  "action": "validate",
  "uid": "68db039b13f4e"
}
```

**Expected Response** (Success):
```json
{
  "status": "success",
  "data": {
    "required_documents": {
      "dni_frontal": "DNI Frontal",
      "dni_trasera": "DNI Trasera"
    },
    "uploaded_documents": {
      "dni_frontal": {
        "file_name": "dni_frontal_68db039b13f4e.pdf",
        "url": "https://webadminpruebas.a-alvarez.com/storage/documents/...",
        "created_at": "2025-12-16 15:30:00",
        "size": 245632
      },
      "dni_trasera": {
        "file_name": "dni_trasera_68db039b13f4e.jpg",
        "url": "https://webadminpruebas.a-alvarez.com/storage/documents/...",
        "created_at": "2025-12-16 15:31:00",
        "size": 189456
      }
    },
    "missing_documents": [],
    "is_complete": true
  },
  "message": "Document validation successful"
}
```

**Curl Command**:
```bash
curl -X POST https://manager.test/api/documents \
  -H "Content-Type: application/json" \
  -d '{"action":"validate","uid":"68db039b13f4e"}'
```

---

## Test Case 2: Upload Single Document (Small File)

**Purpose**: Upload a single file < 1MB to verify basic functionality

**Request** (FormData):
```
action: upload
uid: 68db039b13f4e
type: order
file[]: <file: small_dni.pdf (500KB)>
document_types[]: dni_frontal
```

**Expected Response** (Success):
```json
{
  "status": "success",
  "data": {
    "required_documents": {
      "dni_frontal": "DNI Frontal",
      "dni_trasera": "DNI Trasera"
    },
    "uploaded_documents": {
      "dni_frontal": {
        "file_name": "small_dni.pdf",
        "url": "https://webadminpruebas.a-alvarez.com/storage/documents/...",
        "created_at": "2025-12-16 15:32:00",
        "size": 512000
      }
    },
    "missing_documents": {
      "dni_trasera": "DNI Trasera"
    },
    "is_complete": false
  },
  "message": "Document uploaded successfully"
}
```

**Curl Command**:
```bash
curl -X POST https://manager.test/api/documents \
  -F "action=upload" \
  -F "uid=68db039b13f4e" \
  -F "type=order" \
  -F "file[]=@/path/to/small_dni.pdf" \
  -F "document_types[]=dni_frontal"
```

---

## Test Case 3: Upload Large Document (8-10MB)

**Purpose**: Verify PHP upload limits increased, no PostTooLargeException

**Request** (FormData):
```
action: upload
uid: 68db039b13f4e
type: order
file[]: <file: large_dni.pdf (8MB)>
document_types[]: dni_trasera
```

**Expected Response** (Success):
```json
{
  "status": "success",
  "data": {
    "required_documents": {...},
    "uploaded_documents": {
      "dni_frontal": {...},
      "dni_trasera": {
        "file_name": "large_dni.pdf",
        "size": 8388608
      }
    },
    "missing_documents": {},
    "is_complete": true
  },
  "message": "Document uploaded successfully"
}
```

**Curl Command** (generates 8MB test file):
```bash
# Create 8MB test file
dd if=/dev/zero of=/tmp/test_8mb.bin bs=1M count=8

curl -X POST https://manager.test/api/documents \
  -F "action=upload" \
  -F "uid=68db039b13f4e" \
  -F "type=order" \
  -F "file[]=@/tmp/test_8mb.bin" \
  -F "document_types[]=dni_trasera"
```

---

## Test Case 4: Delete Document

**Purpose**: Remove uploaded document and return to pending state

**Request**:
```json
{
  "action": "delete",
  "uid": "68db039b13f4e",
  "doc_type": "dni_frontal"
}
```

**Expected Response** (Success):
```json
{
  "status": "success",
  "data": {
    "required_documents": {...},
    "uploaded_documents": {
      "dni_trasera": {...}
    },
    "missing_documents": {
      "dni_frontal": "DNI Frontal"
    },
    "is_complete": false
  },
  "message": "Document deleted successfully"
}
```

**Curl Command**:
```bash
curl -X POST https://manager.test/api/documents \
  -H "Content-Type: application/json" \
  -d '{"action":"delete","uid":"68db039b13f4e","doc_type":"dni_frontal"}'
```

---

## Test Case 5: Invalid File Validation

**Purpose**: Verify client-side and server-side validation

**Request** (FormData - invalid file type):
```
action: upload
uid: 68db039b13f4e
type: order
file[]: <file: malware.exe>
document_types[]: dni_frontal
```

**Expected Response** (Client-side blocks - JS shows error):
```
Message: "File 'malware.exe' has an invalid format. Allowed formats: PDF, JPG, PNG, DOC, DOCX."
```

**If bypassed to server**:
```json
{
  "status": "failed",
  "message": "File 'malware.exe' has an invalid type. Allowed formats: PDF, JPG, PNG, DOC, DOCX."
}
```

---

## Test Case 6: File Size Validation

**Purpose**: Verify max 10MB limit enforced

**Request** (FormData - file > 10MB):
```
action: upload
uid: 68db039b13f4e
type: order
file[]: <file: oversized.pdf (15MB)>
document_types[]: dni_frontal
```

**Expected Response** (Client-side blocks):
```
Message: "File 'oversized.pdf' is too large (15.24MB). Maximum size is 10MB."
```

---

## Test Case 7: Empty File Validation

**Purpose**: Prevent zero-byte files

**Request** (FormData - empty file):
```
action: upload
uid: 68db039b13f4e
type: order
file[]: <file: empty.pdf (0 bytes)>
document_types[]: dni_frontal
```

**Expected Response** (Client-side blocks):
```
Message: "File 'empty.pdf' is empty. Please select a valid file."
```

---

## Error Response Examples

### Missing Required Parameter
```json
{
  "status": "failed",
  "message": "Missing required parameter: uid"
}
```

### Document Not Found
```json
{
  "status": "failed",
  "message": "Document not found for uid: invalid_uid"
}
```

### Invalid Action
```json
{
  "status": "failed",
  "message": "Invalid action: invalid_action"
}
```

### File Validation Error
```json
{
  "status": "failed",
  "message": "File 'invalid.txt' has an invalid type. Allowed formats: PDF, JPG, PNG, DOC, DOCX."
}
```

### PostTooLargeException (if limits not increased)
```
HTTP 413 Payload Too Large
```
**Solution**: Deploy `.htaccess` and `.user.ini` to increase limits

---

## Local Testing Commands

### Test 1: Validate Status
```bash
curl -X POST https://manager.test/api/documents \
  -H "Content-Type: application/json" \
  -d '{"action":"validate","uid":"68db039b13f4e"}'
```

### Test 2: Upload Small File
```bash
curl -X POST https://manager.test/api/documents \
  -F "action=upload" \
  -F "uid=68db039b13f4e" \
  -F "type=order" \
  -F "file[]=@/Users/functionbytes/Desktop/test.pdf" \
  -F "document_types[]=dni_frontal"
```

### Test 3: Verify in Tinker
```bash
php artisan tinker
>>> $doc = Document::uid('68db039b13f4e')->first();
>>> $doc->media->count();  // Should be > 0 after upload
>>> $doc->getUploadedDocumentsWithDetails();  // Show uploaded files
>>> $doc->hasAllRequiredDocuments();  // true when all uploaded
```

### Test 4: Check Email Queue
```bash
php artisan queue:work  # Process queued jobs
php artisan queue:failed  # Check failed jobs
tail -50 storage/logs/laravel.log  # Check logs
```

---

## Production Deployment Checklist

- [ ] Deploy `.htaccess` to production
- [ ] Deploy `.user.ini` to production
- [ ] Restart PHP-FPM on production
- [ ] Verify PHP limits: `php -i | grep upload_max_filesize`
- [ ] Test validate endpoint
- [ ] Upload small file (< 1MB)
- [ ] Upload medium file (5-8MB)
- [ ] Verify files in database: `tinker` check media count
- [ ] Verify email queued when complete
- [ ] Test delete functionality
- [ ] Check error logs for any issues

