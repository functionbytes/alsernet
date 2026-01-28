# Mailing Module Shared Components Implementation Guide

## Overview

Three reusable Blade view components have been created for the mailing settings module to promote consistency and reduce code duplication across all features.

## Components Location

All components are located in:
```
modules/Mailing/resources/views/settings/_partials/
```

## Component Files

### 1. `connection-test-modal.blade.php` (11KB)
**Purpose**: Modal dialog for testing server connections

**Status States**:
- Initial (ready to test)
- Loading (testing in progress)
- Success (connection established)
- Error (connection failed)

**Used By**:
- SendingServer controller
- BounceHandler controller
- FeedbackLoopHandler controller
- VerificationServer controller

### 2. `server-type-selector.blade.php` (7.6KB)
**Purpose**: Visual card-based selector for email server types

**Features**:
- 7 predefined server types (SMTP, SendGrid, Mailgun, AWS SES, Postmark, SparkPost, Mailjet)
- Visual cards with icons and descriptions
- Responsive grid layout (auto-adjusts to mobile/tablet/desktop)
- Selection indication with checkmark
- Customizable server types via parameter

**Used By**:
- SendingServer create/edit forms
- BounceHandler create/edit forms
- FeedbackLoopHandler create/edit forms
- VerificationServer create/edit forms

### 3. `quota-progress.blade.php` (7.1KB)
**Purpose**: Progress bar with quota statistics and status indicators

**Features**:
- Color-coded progress bar (green < 50%, yellow 50-80%, orange 80-95%, red > 95%)
- Current usage, remaining, and total quota display
- Dynamic status messages based on usage percentage
- Optional next reset date and time
- Optional action link (e.g., upgrade quota)
- Fully responsive design

**Used By**:
- SendingServer index/show views
- Dashboard quota monitoring widgets
- Server statistics panels

---

## Quick Start Examples

### Connection Test Modal

```blade
<!-- Include the modal -->
@include('mailing::settings._partials.connection-test-modal', [
    'modalId' => 'connectionTestModal',
    'testRoute' => route('settings.mailing.sending-servers.test'),
    'label' => 'Prueba de conexión SMTP'
])

<!-- Trigger button -->
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#connectionTestModal">
    <i class="fas fa-wifi me-2"></i>Probar conexión
</button>
```

**Controller Method** (POST route):
```php
public function test(Request $request)
{
    try {
        $startTime = microtime(true);

        // Your connection test logic
        $this->validateConnection($request);

        $latency = round((microtime(true) - $startTime) * 1000);

        return response()->json([
            'success' => true,
            'message' => 'Conexión exitosa',
            'latency' => $latency,
            'details' => 'Credenciales verificadas'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error de conexión',
            'details' => $e->getMessage()
        ]);
    }
}
```

---

### Server Type Selector

```blade
<form action="{{ route('settings.mailing.sending-servers.store') }}" method="POST">
    @csrf

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0 fw-bold">Tipo de servidor</h6>
        </div>
        <div class="card-body">
            @include('mailing::settings._partials.server-type-selector', [
                'selectedType' => old('type', $server->type ?? ''),
                'inputName' => 'type'
            ])
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-2"></i>Guardar
    </button>
</form>
```

---

### Quota Progress

```blade
<div class="row g-3">
    @foreach($servers as $server)
        <div class="col-md-6 col-lg-4">
            @include('mailing::settings._partials.quota-progress', [
                'current' => $server->emails_sent_this_month,
                'quota' => $server->monthly_quota,
                'label' => 'Cuota mensual - ' . $server->name,
                'unit' => 'correos',
                'resetDate' => $server->quota_reset_date,
                'actionLink' => route('settings.mailing.sending-servers.edit', $server->id),
                'actionLabel' => 'Configurar'
            ])
        </div>
    @endforeach
</div>
```

---

## Parameter Reference

### Connection Test Modal Parameters

```php
[
    'modalId' => 'connectionTestModal',  // Unique modal ID (default)
    'testRoute' => 'api/test-connection', // POST route for testing
    'label' => 'Prueba de conexión'      // Modal title
]
```

### Server Type Selector Parameters

```php
[
    'selectedType' => 'smtp',             // Currently selected type
    'inputName' => 'type',                // Hidden input field name
    'required' => true,                   // Make selection required
    'serverTypes' => [                    // Optional custom types
        [
            'value' => 'custom',
            'label' => 'Custom Provider',
            'icon' => 'fa-cogs',
            'description' => 'Custom Email Provider',
            'color' => 'primary'
        ]
    ]
]
```

### Quota Progress Parameters

```php
[
    'current' => 450,                     // Current usage count
    'quota' => 1000,                      // Total quota limit
    'label' => 'Cuota mensual',           // Display label
    'unit' => 'correos',                  // Unit name
    'resetDate' => $date->toDateTimeString(), // Next reset date (optional)
    'showPercentage' => true,             // Show % badge
    'showStats' => true,                  // Show statistics
    'actionLink' => '/upgrade',           // Action button link (optional)
    'actionLabel' => 'Aumentar cuota'    // Action button text (optional)
]
```

---

## Design Standards

All components follow project standards:

### Bootstrap 5.3
- Responsive grid system with breakpoints
- Mobile-first approach (col-12, col-md-*, col-lg-*)
- Standard spacing utilities (mb-3, p-3, gap-3)
- Card components with headers/bodies/footers

### Font Awesome 6
- Solid icons: `fas fa-*`
- Regular icons: `far fa-*`
- Brand icons: `fab fa-*`
- NO Tabler Icons used

### Colors
- Primary: `#081A28`
- Success: `#13C672`
- Danger: `#FA896B`
- Warning: `#FEC90F`

### Typography
- Section titles: Only first word capitalized (e.g., "Tipo de servidor")
- Font sizes: `fw-bold`, `small`, `fs-6` classes
- Text colors: `text-muted`, `text-success`, `text-danger`

---

## Integration Checklist

When integrating these components into your forms:

- [ ] Include component with correct `@include()` path
- [ ] Pass required parameters (modalId, testRoute, selectedType, etc.)
- [ ] Verify Font Awesome icons are loaded in layout
- [ ] Test on mobile (< 576px), tablet (768px), desktop (> 992px)
- [ ] Ensure test routes return proper JSON format
- [ ] Test form submission with component values
- [ ] Verify CSRF token is available in page
- [ ] Check that hidden inputs submit correct values
- [ ] Test error states and validation messages
- [ ] Verify responsive behavior on all breakpoints

---

## API Response Formats

### Connection Test Response

**Success**:
```json
{
    "success": true,
    "message": "Conexión exitosa",
    "latency": 125,
    "details": "Credenciales verificadas"
}
```

**Error**:
```json
{
    "success": false,
    "message": "Error de conexión",
    "details": "Host not found: smtp.example.com"
}
```

---

## Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari 14+, Chrome Mobile)

---

## Accessibility Features

- ARIA labels on modals and interactive elements
- Semantic HTML structure
- Keyboard navigation support
- Color contrast ratios meet WCAG AA standards
- Loading indicators for async operations
- Error messages clearly displayed

---

## Performance Considerations

- Components use minimal inline styles
- CSS bundled in `@push('styles')` to avoid duplication
- Vanilla JavaScript (no jQuery dependency)
- Responsive images and icons
- Mobile-optimized rendering

---

## Troubleshooting

### Connection Test Modal not working
1. Check that test route returns valid JSON
2. Verify CSRF token meta tag exists in page head
3. Ensure fetch API is available (modern browser)
4. Check browser console for JavaScript errors

### Server Type Selector not highlighting
1. Verify Font Awesome 6 is loaded
2. Check that `selectedType` parameter matches a server type value
3. Inspect element to verify hidden input has correct value
4. Look for CSS conflicts with custom styles

### Quota Progress not displaying correctly
1. Ensure `current` and `quota` are integers
2. Check date format for `resetDate` (ISO 8601 format)
3. Verify card isn't constrained by parent container width
4. Test with different quota values to see color changes

---

## Documentation

Detailed documentation for each component is available in:
```
modules/Mailing/resources/views/settings/_partials/README.md
```

---

## Related Files

- Models: `modules/Mailing/app/Models/*` (SendingServer, BounceHandler, etc.)
- Controllers: `modules/Mailing/app/Http/Controllers/Settings/*`
- Routes: `modules/Mailing/routes/web.php`
- Views: `modules/Mailing/resources/views/settings/*`

---

## Next Steps

After implementing these components:
1. Update SendingServer create/edit forms to use components
2. Add connection test functionality to controller
3. Update BounceHandler forms with type selector
4. Add quota monitoring to dashboard
5. Create integration tests for components
6. Document custom implementations in project wiki

---

## Questions & Support

For component-specific issues:
1. Check README.md in _partials directory
2. Review example implementations in existing views
3. Check browser console for JavaScript errors
4. Verify data format matches expected structure
