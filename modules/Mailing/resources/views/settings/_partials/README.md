# Mailing Settings Shared Components

This directory contains reusable Blade view components for the mailing settings module. These components promote consistency and reduce code duplication across all mailing features.

## Components

### 1. Connection Test Modal (`connection-test-modal.blade.php`)

A Bootstrap modal component for testing server connections with real-time feedback.

#### Features
- **Three states**: Initial (ready), Loading (testing), Success (connected), Error (failed)
- **Connection details**: Status, latency, credentials verification
- **Error handling**: Detailed error messages and troubleshooting tips
- **Responsive design**: Works on all device sizes
- **Accessibility**: Proper ARIA labels and semantic HTML

#### Usage

**Basic Example:**
```blade
@include('mailing::settings._partials.connection-test-modal', [
    'modalId' => 'connectionTestModal',
    'testRoute' => route('settings.mailing.sending-servers.test'),
    'label' => 'Prueba de conexión SMTP'
])

<!-- Button to trigger modal -->
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#connectionTestModal">
    <i class="fas fa-wifi me-2"></i>Probar conexión
</button>
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `modalId` | string | `connectionTestModal` | Unique ID for the modal |
| `testRoute` | string | `#` | POST route for connection testing |
| `label` | string | `Prueba de conexión` | Modal title label |

#### Server-Side Implementation

The test route should handle POST requests and return JSON:

```php
// Controller Method
public function test(Request $request)
{
    try {
        $startTime = microtime(true);

        // Test connection logic here
        $connected = $this->testConnection($request);

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

#### JavaScript API

You can also call the `testConnection()` function directly:

```javascript
testConnection(testRoute, 'connectionTestModal');
```

---

### 2. Server Type Selector (`server-type-selector.blade.php`)

A visual card-based selector for choosing email server types.

#### Features
- **Visual cards**: Icons, descriptions, and colors for each type
- **Default types**: SMTP, SendGrid, Mailgun, AWS SES, Postmark, SparkPost, Mailjet
- **Responsive grid**: Auto-adjusts for mobile, tablet, and desktop
- **Selection indicator**: Visual feedback for selected type
- **Form integration**: Hidden input for form submission
- **Customizable**: Accept custom server types

#### Usage

**Basic Example (Default Types):**
```blade
@include('mailing::settings._partials.server-type-selector', [
    'selectedType' => old('type', $server->type ?? ''),
    'inputName' => 'type'
])
```

**Custom Server Types:**
```blade
@include('mailing::settings._partials.server-type-selector', [
    'selectedType' => old('type', $server->type ?? ''),
    'inputName' => 'type',
    'serverTypes' => [
        [
            'value' => 'custom_smtp',
            'label' => 'Custom SMTP',
            'icon' => 'fa-cogs',
            'description' => 'Custom SMTP Configuration',
            'color' => 'primary'
        ],
        [
            'value' => 'custom_api',
            'label' => 'Custom API',
            'icon' => 'fa-cube',
            'description' => 'Custom API Integration',
            'color' => 'info'
        ]
    ]
])
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `selectedType` | string | `''` | Currently selected type value |
| `serverTypes` | array | Default types | Array of type objects |
| `inputName` | string | `type` | Name attribute for hidden input |
| `required` | boolean | `true` | Make selection required |

#### Server Type Object Structure

```php
[
    'value' => 'smtp',           // Form value
    'label' => 'SMTP',           // Display label
    'icon' => 'fa-envelope',     // Font Awesome icon
    'description' => 'SMTP Server', // Brief description
    'color' => 'primary'         // Bootstrap color (primary, info, success, etc.)
]
```

#### Default Server Types

1. **SMTP** - `fa-envelope` - Personal SMTP server
2. **SendGrid** - `fa-paper-plane` - SendGrid API
3. **Mailgun** - `fa-gun` - Mailgun API
4. **Amazon SES** - `fa-amazon` - AWS Simple Email Service
5. **Postmark** - `fa-stamp` - Postmark API
6. **SparkPost** - `fa-fire` - SparkPost API
7. **Mailjet** - `fa-jet-fighter` - Mailjet API

#### JavaScript API

Select a type programmatically:

```javascript
const card = document.querySelector('[data-type-value="smtp"]');
selectServerType(card, 'type');
```

---

### 3. Quota Progress (`quota-progress.blade.php`)

A progress bar component with quota statistics and status indicators.

#### Features
- **Color-coded progress**: Green (<50%), Yellow (50-80%), Orange (80-95%), Red (>95%)
- **Statistics display**: Current usage, remaining, and total quota
- **Status messages**: Dynamic messages based on usage percentage
- **Reset information**: Shows next quota reset date and time
- **Responsive design**: Works on all screen sizes
- **Optional actions**: Can include upgrade/increase links

#### Usage

**Basic Example:**
```blade
@include('mailing::settings._partials.quota-progress', [
    'current' => 450,
    'quota' => 1000,
    'label' => 'Cuota mensual',
    'unit' => 'correos'
])
```

**With Reset Date:**
```blade
@include('mailing::settings._partials.quota-progress', [
    'current' => 450,
    'quota' => 1000,
    'label' => 'Cuota mensual',
    'unit' => 'correos',
    'resetDate' => now()->addDays(5)->toDateTimeString()
])
```

**With Action Link:**
```blade
@include('mailing::settings._partials.quota-progress', [
    'current' => 450,
    'quota' => 1000,
    'label' => 'Cuota mensual',
    'unit' => 'correos',
    'resetDate' => now()->addDays(5)->toDateTimeString(),
    'actionLink' => route('settings.mailing.upgrade-quota'),
    'actionLabel' => 'Aumentar cuota'
])
```

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `current` | integer | `0` | Current usage count |
| `quota` | integer | `0` | Total quota limit |
| `label` | string | `Cuota de envío` | Display label |
| `unit` | string | `correos` | Unit label (emails, messages, etc.) |
| `resetDate` | string | `null` | Next reset date (ISO format) |
| `showPercentage` | boolean | `true` | Show percentage badge |
| `showStats` | boolean | `true` | Show usage statistics |
| `actionLink` | string | `null` | Optional action button link |
| `actionLabel` | string | `null` | Optional action button text |

#### Color Status Guide

| Usage | Color | Icon | Status |
|-------|-------|------|--------|
| < 50% | Green | `fa-smile` | OK - No action needed |
| 50-80% | Yellow | `fa-exclamation-triangle` | Caution - Monitor usage |
| 80-95% | Orange | `fa-exclamation-circle` | Warning - Consider upgrading |
| > 95% | Red | `fa-circle-exclamation` | Critical - Upgrade now |

#### Responsive Behavior

- **Desktop**: Full card with shadow and hover effects
- **Mobile**: Simplified design with reduced spacing
- **Tablet**: Medium size with all features visible

---

## Integration Examples

### Sending Server Form with Connection Test

```blade
<div class="row g-3">
    <div class="col-lg-8">
        <!-- Server configuration form -->
    </div>
    <div class="col-lg-4">
        <button type="button" class="btn btn-outline-primary w-100 mb-3"
                data-bs-toggle="modal"
                data-bs-target="#connectionTestModal">
            <i class="fas fa-wifi me-2"></i>Probar conexión
        </button>
    </div>
</div>

@include('mailing::settings._partials.connection-test-modal', [
    'modalId' => 'connectionTestModal',
    'testRoute' => route('settings.mailing.sending-servers.test')
])
```

### Server Selection Workflow

```blade
<form action="{{ route('settings.mailing.sending-servers.store') }}" method="POST">
    @csrf

    <!-- Step 1: Select Server Type -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0 fw-bold">Selecciona el tipo de servidor</h6>
        </div>
        <div class="card-body">
            @include('mailing::settings._partials.server-type-selector', [
                'selectedType' => old('type')
            ])
        </div>
    </div>

    <!-- Step 2: Configuration fields (shown conditionally via JS) -->
    <div id="configurationCard" class="card mb-4" style="display: none;">
        <!-- Configuration fields here -->
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-2"></i>Guardar servidor
    </button>
</form>

<script>
document.addEventListener('change', function(e) {
    if (e.target.id === 'type') {
        const selectedType = e.target.value;
        document.getElementById('configurationCard').style.display =
            selectedType ? 'block' : 'none';
    }
});
</script>
```

### Dashboard with Quota Monitoring

```blade
<div class="row g-3">
    @foreach($servers as $server)
        <div class="col-md-6 col-lg-4">
            @include('mailing::settings._partials.quota-progress', [
                'current' => $server->emails_sent_this_month,
                'quota' => $server->monthly_quota,
                'label' => 'Cuota - ' . $server->name,
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

## Bootstrap Classes Used

All components use Bootstrap 5.3 classes:
- Grid system: `row`, `col-*`, `col-md-*`, `col-lg-*`
- Cards: `card`, `card-header`, `card-body`, `card-footer`
- Buttons: `btn`, `btn-primary`, `btn-outline-secondary`
- Forms: `form-control`, `form-label`, `invalid-feedback`
- Alerts: `alert`, `alert-success`, `alert-danger`, `alert-warning`, `alert-info`
- Utilities: `mb-3`, `p-3`, `fw-bold`, `text-muted`, `gap-3`, `d-flex`, `align-items-center`

---

## Font Awesome 6 Icons

All icons use Font Awesome 6:
- `fas fa-*` - Solid icons
- `far fa-*` - Regular icons
- `fab fa-*` - Brand icons

Available icons in components:
- `fa-envelope`, `fa-paper-plane`, `fa-gun`, `fa-amazon`, `fa-stamp`, `fa-fire`, `fa-jet-fighter` (Server types)
- `fa-wifi`, `fa-check-circle`, `fa-exclamation-circle`, `fa-sync-alt`, `fa-smile`, `fa-exclamation-triangle`, `fa-circle-exclamation`, `fa-redo`, `fa-arrow-up` (UI actions)

---

## Browser Support

- Chrome/Chromium (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

---

## Accessibility

All components follow WCAG 2.1 AA standards:
- Proper ARIA labels on modals and buttons
- Semantic HTML structure
- Color contrast ratios meet standards
- Keyboard navigation support
- Focus management

---

## Performance Notes

- Components use minimal inline styles
- CSS is in `@push('styles')` sections to avoid duplication
- JavaScript uses vanilla JS, no external dependencies
- Components lazy-load content as needed
- Optimized for mobile rendering

---

## Troubleshooting

### Connection Test Not Working
- Ensure the test route returns proper JSON format
- Check CSRF token is passed correctly
- Verify server endpoint is accessible

### Type Selector Not Updating
- Make sure hidden input name matches form field name
- Check browser console for JavaScript errors
- Verify Font Awesome icons are loaded

### Quota Progress Not Displaying
- Ensure `current` and `quota` are integers
- Check date format for `resetDate` (ISO 8601)
- Verify card layout isn't constrained by parent

---

## Future Enhancements

- [ ] Add loading skeleton for quota progress
- [ ] Support for custom color themes
- [ ] Internationalization (i18n) support
- [ ] Export quota data to CSV
- [ ] Real-time connection status monitoring
