# Mailing Components Visual Reference

This guide provides visual examples and use cases for the three shared components.

---

## 1. Connection Test Modal

### Component States

```
┌─ Initial State (Ready) ─────────────────────────┐
│                                                   │
│  🔌 Prueba de conexión                      [×]  │
│ ─────────────────────────────────────────────── │
│                                                   │
│              🔌 (icon)                          │
│                                                   │
│     Haz clic en "Iniciar prueba" para         │
│      verificar la conexión con el servidor      │
│                                                   │
│ [Iniciar prueba]           [Cerrar]             │
└───────────────────────────────────────────────┘

┌─ Loading State (Testing) ───────────────────────┐
│                                                   │
│  🔌 Prueba de conexión                      [×]  │
│ ─────────────────────────────────────────────── │
│                                                   │
│              ⟳ (spinner)                        │
│                                                   │
│          Probando conexión...                   │
│                                                   │
│ [Iniciar prueba]           [Cerrar]             │
└───────────────────────────────────────────────┘

┌─ Success State (Connected) ────────────────────┐
│                                                   │
│  🔌 Prueba de conexión                      [×]  │
│ ─────────────────────────────────────────────── │
│                                                   │
│  ✓ ¡Conexión exitosa!                          │
│    El servidor respondió correctamente           │
│                                                   │
│  ┌─────────────────────────────────┐           │
│  │ Estado      │ Conectado    ✓    │           │
│  │ Latencia    │ 125 ms           │           │
│  │ Información │ Credenciales     │           │
│  │             │ verificadas      │           │
│  └─────────────────────────────────┘           │
│                                                   │
│ [Reintentar]              [Cerrar]              │
└───────────────────────────────────────────────┘

┌─ Error State (Failed) ──────────────────────────┐
│                                                   │
│  🔌 Prueba de conexión                      [×]  │
│ ─────────────────────────────────────────────── │
│                                                   │
│  ✕ ¡Error de conexión!                          │
│    No se pudo conectar con el servidor           │
│                                                   │
│  ┌─ Detalles del error ──────────────┐          │
│  │ Host not found: smtp.example.com  │          │
│  └───────────────────────────────────┘          │
│                                                   │
│  ℹ Sugerencias:                                 │
│   • Verifica que la configuración sea correcta  │
│   • Comprueba la conexión de red               │
│   • Asegúrate de que las credenciales sean ...  │
│   • Revisa si el firewall permite la conexión   │
│                                                   │
│ [Reintentar]              [Cerrar]              │
└───────────────────────────────────────────────┘
```

### Desktop Layout
```
Form                          Modal
┌────────────────────┐  ┌──────────────────┐
│ Name: SMTP Server  │  │ Connection Test  │
│ Type: [Dropdown]   │  │ ─────────────    │
│ Host: mail.ex...   │  │ ✓ Connection OK  │
│ Port: 587          │  │   Latency: 125ms │
│ [Test Connection]  │  │ ─────────────    │
│   ↓                │  │ [Retry] [Close]  │
│ [Cancel] [Save]    │  └──────────────────┘
└────────────────────┘
```

### Mobile Layout
```
┌─────────────────┐
│ Connection Test │
│ ───────────────│
│ ✓ Success       │
│   Latency: 125  │
│   ms            │
│ ───────────────│
│ [Retry][Close]  │
└─────────────────┘
```

### Usage Pattern

```blade
<!-- Form Section -->
<form method="POST" action="{{ route('store') }}">
    <!-- Other form fields -->

    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary"
                data-bs-toggle="modal"
                data-bs-target="#connectionTestModal">
            <i class="fas fa-wifi me-2"></i>Probar conexión
        </button>
    </div>
</form>

<!-- Modal Component -->
@include('mailing::settings._partials.connection-test-modal', [
    'modalId' => 'connectionTestModal',
    'testRoute' => route('test'),
    'label' => 'Prueba de conexión'
])
```

---

## 2. Server Type Selector

### Component Grid

```
┌─ Desktop View (≥992px) ──────────────────────────────────────┐
│                                                                │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐    │
│  │ ✓        │  │          │  │          │  │          │    │
│  │  📧 SMTP │  │ 📬 SendGrid│ │  🔫 Mailgun│ │ 🏢 AWS SES│ │
│  │ Servidor │  │ API      │  │ API      │  │ Service  │    │
│  │ SMTP ...  │  │          │  │          │  │          │    │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘    │
│                                                                │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                   │
│  │          │  │          │  │          │                   │
│  │ 🪶 Postmark│ │ 🔥 SparkPost│ │ 🛩️ Mailjet│                │
│  │ API      │  │ API      │  │ API      │                   │
│  │          │  │          │  │          │                   │
│  └──────────┘  └──────────┘  └──────────┘                   │
│                                                                │
└────────────────────────────────────────────────────────────┘

┌─ Tablet View (≥768px) ────────────────────────────────┐
│                                                         │
│  ┌──────────┐  ┌──────────┐                           │
│  │ ✓        │  │          │                           │
│  │  📧 SMTP │  │ 📬 SendGrid│                          │
│  │ Servidor │  │ API      │                           │
│  │ SMTP ... │  │          │                           │
│  └──────────┘  └──────────┘                           │
│                                                         │
│  ┌──────────┐  ┌──────────┐                           │
│  │          │  │          │                           │
│  │ 🔫 Mailgun │ │ 🏢 AWS SES│                           │
│  │ API      │  │ Service  │                           │
│  │          │  │          │                           │
│  └──────────┘  └──────────┘                           │
│                                                         │
└─────────────────────────────────────────────────────┘

┌─ Mobile View (<768px) ────────────────┐
│                                        │
│  ┌──────────┐                        │
│  │ ✓        │                        │
│  │  📧 SMTP │                        │
│  │ Servidor │                        │
│  │ SMTP ... │                        │
│  └──────────┘                        │
│                                        │
│  ┌──────────┐                        │
│  │          │                        │
│  │ 📬 SendGrid│                        │
│  │ API      │                        │
│  │          │                        │
│  └──────────┘                        │
│                                        │
│  ┌──────────┐                        │
│  │          │                        │
│  │ 🔫 Mailgun │                        │
│  │ API      │                        │
│  │          │                        │
│  └──────────┘                        │
│                                        │
└────────────────────────────────────┘
```

### Card States

```
┌─ Default (Unselected) ──┐    ┌─ Hover ────────────┐    ┌─ Selected ──────┐
│                         │    │                    │    │ ✓               │
│        📧              │    │        📧           │    │    📧            │
│                         │    │  (lifted shadow)   │    │                  │
│      SMTP              │    │      SMTP          │    │  SMTP            │
│   Personal...           │    │   Personal...       │    │ Personal...      │
│                         │    │                    │    │                  │
│         (border)       │    │    (shadow)        │    │  ════ (border)  │
└─────────────────────────┘    └────────────────────┘    └─────────────────┘
```

### Color Coding

```
SendGrid: 🔷 info      Mailgun: 🟨 warning   AWS SES: ⚫ secondary
SMTP: 🔵 primary       Postmark: 🔴 danger    SparkPost: 🟢 success
Mailjet: 🔵 primary
```

### Usage Pattern

```blade
<!-- Type Selector in Form -->
<form method="POST" action="{{ route('store') }}">
    @csrf

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0 fw-bold">Selecciona tipo de servidor</h6>
        </div>
        <div class="card-body">
            @include('mailing::settings._partials.server-type-selector', [
                'selectedType' => old('type', $server->type ?? ''),
                'inputName' => 'type'
            ])
        </div>
    </div>

    <!-- Configuration fields appear based on selection -->
    <button type="submit" class="btn btn-primary">Guardar</button>
</form>

<!-- Hidden form field automatically created -->
<!-- <input type="hidden" id="type" name="type" value=""> -->
```

---

## 3. Quota Progress Component

### Color Indicators

```
Usage Level          Color    Icon               Message
─────────────────────────────────────────────────────────
< 50%               Green    😊 fa-smile         ✓ OK - No action needed
50-80%              Yellow   ⚠️  fa-exclamation-triangle  Caution - Monitor usage
80-95%              Orange   ⚠️  fa-exclamation-circle    Warning - Consider upgrading
> 95%               Red      ❌ fa-circle-exclamation    Critical - Upgrade now
```

### Progress Bar States

```
┌─ 25% Usage (Green) ────────────────────────────────┐
│ Cuota mensual                           25%         │
│ ████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │
│                                                      │
│ Utilizado    │  Disponible                          │
│ 250 correos  │  750 correos                         │
│                                                      │
│ Cuota total: 1,000 correos                         │
│                                                      │
│ ✓ Cuota disponible - Sin preocupaciones            │
└────────────────────────────────────────────────────┘

┌─ 65% Usage (Yellow) ────────────────────────────────┐
│ Cuota mensual                           65%         │
│ ██████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │
│                                                      │
│ Utilizado    │  Disponible                          │
│ 650 correos  │  350 correos                         │
│                                                      │
│ Cuota total: 1,000 correos                         │
│                                                      │
│ ⚠️  Cuota moderada - Monitorea tu uso              │
└────────────────────────────────────────────────────┘

┌─ 90% Usage (Orange) ───────────────────────────────┐
│ Cuota mensual                           90%         │
│ █████████████████████░░░░░░░░░░░░░░░░░░░░░░░░░ │
│                                                      │
│ Utilizado    │  Disponible                          │
│ 900 correos  │  100 correos                         │
│                                                      │
│ Cuota total: 1,000 correos                         │
│                                                      │
│ ⚠️  Cuota casi llena - Aumenta el límite pronto   │
│                                                      │
│ [Aumentar cuota]                                   │
└────────────────────────────────────────────────────┘

┌─ 98% Usage (Red) ──────────────────────────────────┐
│ Cuota mensual                           98%         │
│ ███████████████████████████░░░░░░░░░░░░░░░░░░ │
│                                                      │
│ Utilizado    │  Disponible                          │
│ 980 correos  │  20 correos                          │
│                                                      │
│ Cuota total: 1,000 correos                         │
│                                                      │
│ ❌ Cuota casi agotada - Aumenta el límite pronto   │
│                                                      │
│ [Aumentar cuota]                                   │
└────────────────────────────────────────────────────┘
```

### Reset Date Information

```
┌─ With Reset Information ───────────────────────────┐
│ Cuota mensual                           45%         │
│ ████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │
│                                                      │
│ Utilizado    │  Disponible                          │
│ 450 correos  │  550 correos                         │
│                                                      │
│ Cuota total: 1,000 correos                         │
│                                                      │
│ ✓ Cuota disponible - Sin preocupaciones            │
│                                                      │
│ ─────────────────────────────────────────────────│
│ 🔄 Siguiente reinicio                              │
│ 02/02/2026 00:00                                   │
│ en 5 días                                           │
│                                                      │
│ [Aumentar cuota]                                   │
└────────────────────────────────────────────────────┘
```

### Dashboard Grid

```
┌─────────────────────────────────────────────────────────────────┐
│ Monitoreo de cuota                                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐  │
│  │ Servidor 1       │  │ Servidor 2       │  │ Servidor 3   │  │
│  │ 45%              │  │ 75%              │  │ 92%          │  │
│  │ ████░░░░░░░░░░ │  │ ███████░░░░░░░░ │  │ █████████░░ │  │
│  │                  │  │                  │  │              │  │
│  │ 450/1000         │  │ 750/1000         │  │ 920/1000     │  │
│  │ ✓ OK            │  │ ⚠️  Monitor       │  │ ⚠️  Warning   │  │
│  │ [Config]        │  │ [Config]         │  │ [Upgrade]    │  │
│  └──────────────────┘  └──────────────────┘  └──────────────┘  │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

### Mobile Card

```
┌──────────────────┐
│ Cuota mensual  ✓ │
│ 45%              │
│ ████░░░░░░░░░░ │
│                  │
│ Utilizado        │
│ 450 correos      │
│                  │
│ Disponible       │
│ 550 correos      │
│                  │
│ Cuota: 1,000     │
│                  │
│ ✓ Sin peligro    │
│                  │
│ 🔄 Resetea       │
│ en 5 días        │
│                  │
│ [Aumentar]       │
└──────────────────┘
```

### Usage Pattern

```blade
<!-- Single Server Card -->
<div class="col-md-4">
    @include('mailing::settings._partials.quota-progress', [
        'current' => $server->emails_sent_this_month,
        'quota' => $server->monthly_quota,
        'label' => 'Cuota - ' . $server->name,
        'unit' => 'correos',
        'resetDate' => $server->quota_reset_date,
        'actionLink' => route('upgrade', $server->id),
        'actionLabel' => 'Aumentar cuota'
    ])
</div>

<!-- Grid of All Servers -->
<div class="row g-3">
    @foreach($servers as $server)
        <div class="col-md-6 col-lg-4">
            @include('mailing::settings._partials.quota-progress', [
                'current' => $server->emails_sent_this_month,
                'quota' => $server->monthly_quota,
                'label' => 'Cuota - ' . $server->name,
                'unit' => 'correos',
                'resetDate' => $server->quota_reset_date
            ])
        </div>
    @endforeach
</div>
```

---

## Combined Layout Example

### Sending Server Create/Edit Form

```
┌──────────────────────────────────────────────────────────────┐
│ Nuevo servidor de envío                              │ < > │ │
├──────────────────────────────────────────────────────────────┤
│                                                                │
│ 1. Selecciona tipo de servidor                             │
│ ┌──────────────────────────────────────────────────────┐    │
│ │  ┌──────────┐  ┌──────────┐  ┌──────────┐           │    │
│ │  │ ✓ SMTP   │  │ SendGrid │  │ Mailgun  │  ...      │    │
│ │  └──────────┘  └──────────┘  └──────────┘           │    │
│ └──────────────────────────────────────────────────────┘    │
│                                                                │
│ 2. Configura el servidor                                   │
│ ┌──────────────────────────────────────────────────────┐    │
│ │ Name: ________________                                │    │
│ │ Type: SMTP (selected)                               │    │
│ │ Host: ________________  Port: ________              │    │
│ │ Username: ____________  Password: ________          │    │
│ │                                                       │    │
│ │ [Probar conexión]                                    │    │
│ └──────────────────────────────────────────────────────┘    │
│                                                                │
│ [Cancelar]  [Guardar servidor]                              │
│                                                                │
└──────────────────────────────────────────────────────────────┘

        Connection Test Modal
        ┌──────────────────┐
        │ Prueba conexión  │
        │ ─────────────── │
        │ ✓ Éxito         │
        │ Latencia: 125ms │
        │ ─────────────── │
        │ [Reintentar]    │
        │ [Cerrar]        │
        └──────────────────┘
```

---

## Responsive Behavior

### Desktop (≥992px)
- Server type selector: 4 columns
- Forms: 2 columns for fields
- Full modals with detailed information
- Quota cards: 3 in a row

### Tablet (768px - 991px)
- Server type selector: 2 columns
- Forms: 1 column (full width)
- Modals centered on screen
- Quota cards: 2 in a row

### Mobile (<768px)
- Server type selector: 1 column
- Forms: 1 column (full width)
- Modals full width with bottom spacing
- Quota cards: 1 in a row (stacked)
- Reduced padding and margins
- Touch-friendly button sizes

---

## Color Scheme Reference

```
Primary:    #081A28 (Alsernet Dark Blue)
Success:    #13C672 (Green)
Danger:     #FA896B (Red/Orange)
Warning:    #FEC90F (Yellow)
Secondary:  #6C757D (Gray)

Quota Colors:
  < 50%:    Success (#13C672) + smile icon
  50-80%:   Warning (#FEC90F) + caution icon
  80-95%:   Orange (#FF9800) + warning icon
  > 95%:    Danger (#FA896B) + critical icon
```

---

## Typography Hierarchy

```
Modal Title:        h5 / fw-bold
Component Label:    h6 / fw-bold
Card Title:         h6 / fw-bold
Body Text:          small / p / span
Help Text:          small / text-muted
Error Messages:     small / text-danger / field-validation-error
```

---

## Icons Used

```
Connection Modal:
  fa-wifi           Test indicator
  fa-check-circle   Success state
  fa-exclamation-circle  Error state
  fa-spinner        Loading indicator

Type Selector:
  fa-envelope       SMTP
  fa-paper-plane    SendGrid
  fa-gun            Mailgun
  fa-amazon         AWS SES
  fa-stamp          Postmark
  fa-fire           SparkPost
  fa-jet-fighter    Mailjet

Quota Progress:
  fa-smile          < 50% (OK)
  fa-exclamation-triangle   50-80% (Caution)
  fa-exclamation-circle     80-95% (Warning)
  fa-circle-exclamation     > 95% (Critical)
  fa-redo           Reset date
```

---

This visual reference guide helps developers understand how the components appear and behave across different screen sizes and states.
