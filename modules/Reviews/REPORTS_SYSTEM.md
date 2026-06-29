# Reports System - Reviews Module

## Overview
Complete reporting system for generating statistical and detailed reports from Google My Business reviews.

## Components

### 1. Database
**Table**: `generated_reports`
- Tracks all generated reports with metadata
- Auto-expires after 30 days
- Supports multiple formats (CSV, Excel, PDF)
- User-scoped access control

### 2. Service Layer
**Class**: `Modules\Reviews\Services\ReviewReportService`

#### Report Types:
1. **Location Summary** (`location_summary`)
   - Statistics grouped by location
   - Total reviews, avg rating, reply rate per location
   - Rating distribution breakdown

2. **Period Analysis** (`period_analysis`)
   - Time-series trends (daily/weekly/monthly)
   - Evolution over selected period
   - Configurable grouping

3. **Comparison** (`comparison`)
   - Side-by-side comparison of two periods
   - Shows changes in reviews, ratings, reply rates
   - Percentage change calculations

4. **Response Performance** (`response_performance`)
   - Reply rates by rating
   - Average response time in hours
   - Identifies gaps in customer service

5. **Detailed Export** (`detailed_export`)
   - Complete review data export
   - All fields including comments, replies, moderation status

### 3. Job Queue
**Class**: `Modules\Reviews\Jobs\GenerateReportJob`
- Async report generation (queue: `reports`)
- Supports CSV, Excel, PDF formats
- Auto-cleanup on failure
- Stores in `storage/app/reports/`

### 4. Controller
**Class**: `Modules\Reviews\Http\Controllers\ReportController`

**Routes**:
- `GET /reviews/reports` - Report generator UI
- `POST /reviews/reports/generate` - Queue report generation
- `GET /reviews/reports/list` - AJAX list of user's reports
- `GET /reviews/reports/{id}/download` - Download report file
- `DELETE /reviews/reports/{id}` - Delete report

### 5. Views
**Location**: `modules/Reviews/resources/views/reports/`
- `index.blade.php` - Main report generator page
- `_generator-form.blade.php` - Form partial with dynamic fields
- `_reports-list.blade.php` - Recent reports table
- `pdf/location_summary.blade.php` - PDF template example

### 6. Commands
```bash
# Generate report via CLI
php artisan reviews:report --location=1 --from=2024-01-01 --to=2024-12-31 --format=csv

# Clean up expired reports
php artisan reviews:reports-cleanup --days=30
```

## Usage Flow

### Generate Report from UI
1. User visits `/reviews/reports`
2. Selects report type, format, date range, locations
3. Submits form → POST to `/reviews/reports/generate`
4. `GeneratedReport` record created with status `processing`
5. `GenerateReportJob` dispatched to queue
6. Job generates report file and updates record to `completed`
7. User downloads from reports list

### Generate Report from CLI
```bash
php artisan reviews:report --location=5 --from=2024-01-01 --format=excel
```

## File Formats

### CSV
- Simple text format
- Good for imports and basic analysis
- Smaller file size

### Excel (.xlsx)
- Uses `Maatwebsite/Excel` package
- Multiple sheets support
- Styled headers
- Best for business users

### PDF
- Uses `barryvdh/laravel-dompdf`
- Formatted reports with branding
- Non-editable, presentation-ready
- Currently only for summary reports

## Security & Authorization

### Policy: `GeneratedReportPolicy`
- `viewAny`: User can access reports index
- `view`: User owns report OR has `view_all_reports` permission
- `create`: User has `create_reports` permission
- `delete`: User owns report OR has `delete_all_reports` permission

### Required Permissions
Add to your permissions seeder:
```php
'view_reports',
'create_reports',
'view_all_reports',
'delete_all_reports',
```

## Maintenance

### Auto-Cleanup
Reports expire after 30 days (configurable in `GeneratedReport::markAsCompleted()`).

Schedule in `app/Console/Kernel.php`:
```php
$schedule->command('reviews:reports-cleanup --days=30')->daily();
```

### File Storage
Reports stored in `storage/app/reports/`.
Ensure proper disk permissions for the queue worker.

## Frontend Integration

### Dynamic Form
The report type selector dynamically shows/hides relevant fields:
- **Standard reports**: date_from, date_to, locations
- **Comparison**: period1_from/to, period2_from/to
- **Period analysis**: group_by (day/week/month)

### Auto-Refresh
Reports list auto-refreshes every 30 seconds to show processing status updates.

### AJAX Endpoints
```javascript
// Load reports list
GET /reviews/reports/list

// Delete report
DELETE /reviews/reports/{id}
```

## Export Classes

### ReportExport
**Location**: `Modules\Reviews\app\Exports\ReportExport.php`
- Implements `Maatwebsite\Excel\Concerns\FromArray`
- Dynamic headings based on report type
- Styled headers
- Custom sheet titles

## Error Handling

### Job Failures
- Max 3 retries with 60s backoff
- Errors logged to Laravel log
- Report status set to `failed`
- `error_message` column stores exception message

### Validation
**FormRequest**: `GenerateReportRequest`
- Validates report type, format, dates
- Ensures date ranges are logical
- Checks location IDs exist
- Custom error messages in Spanish

## Testing

### Manual Test
1. Visit `/reviews/reports`
2. Select "Resumen por ubicación"
3. Choose Excel format
4. Set last 30 days
5. Submit → verify queued message
6. Run `php artisan queue:work reports`
7. Refresh page → download button appears
8. Download and verify file

### Via Tinker
```php
$service = app(\Modules\Reviews\Services\ReviewReportService::class);
$data = $service->generateLocationReport([
    'date_from' => '2024-01-01',
    'date_to' => '2024-12-31',
]);
dd($data);
```

## Future Enhancements
- Chart generation (using ChartJS or similar)
- Email reports when ready
- Scheduled recurring reports
- More PDF templates for all report types
- Report templates (saved filter presets)
- Custom report builder (drag-and-drop fields)

## Dependencies
- `maatwebsite/excel` - Excel generation
- `barryvdh/laravel-dompdf` - PDF generation
- Laravel Queue system
- Bootstrap 5.3 for UI
- jQuery for AJAX interactions

## File Locations
```
modules/Reviews/
├── app/
│   ├── Console/
│   │   └── CleanExpiredReportsCommand.php
│   ├── Exports/
│   │   └── ReportExport.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── ReportController.php
│   │   └── Requests/
│   │       └── GenerateReportRequest.php
│   ├── Jobs/
│   │   └── GenerateReportJob.php
│   ├── Models/
│   │   └── GeneratedReport.php
│   ├── Policies/
│   │   └── GeneratedReportPolicy.php
│   └── Services/
│       └── ReviewReportService.php
├── database/
│   └── migrations/
│       └── 2026_02_27_160235_create_generated_reports_table.php
└── resources/
    └── views/
        └── reports/
            ├── index.blade.php
            ├── _generator-form.blade.php
            ├── _reports-list.blade.php
            └── pdf/
                └── location_summary.blade.php
```
