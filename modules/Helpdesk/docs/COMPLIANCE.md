# Compliance — GDPR + 2FA

Implementaciones para cumplir con regulaciones de privacidad (GDPR / CCPA) y endurecer el login de agentes.

## 1. GDPR Data Export

Cuando un cliente solicita "Quiero saber qué datos tienes sobre mí" (Art. 15 GDPR — Right of Access):

### Cómo procesar la solicitud

```bash
# Como admin, en la URL del cliente:
GET /panel/helpdesk/customers/{customer}/gdpr-export
```

Esto descarga un ZIP con:
- `customer.json` — todos los campos PII del cliente
- `conversations/` — un JSON por conversación con todos los items
- `attachments/` — copia de los archivos enviados/recibidos
- `csat_ratings.json` — encuestas CSAT respondidas
- `nps_ratings.json` — encuestas NPS respondidas
- `audit_logs.json` — historial de cambios sobre este cliente

### Servicio

`Modules\Helpdesk\Services\Compliance\GdprExportService::exportCustomer($customer)` retorna el array.
`exportToZip($customer)` retorna el path al ZIP generado en `storage/app/gdpr-exports/`.

Cada export se loggea en `helpdesk_audit_logs` con `action='gdpr.export'`.

## 2. GDPR Data Deletion

Cuando un cliente solicita "Borrad mis datos" (Art. 17 GDPR — Right to Erasure):

### Soft delete (recomendado, default)

```
DELETE /panel/helpdesk/customers/{customer}/gdpr-delete?hard=0
```

Anonimiza pero conserva estadísticas:
- `customer.name` → `Cliente eliminado #{id}`
- `customer.email`, `phone`, `whatsapp_phone`, `facebook_psid`, `instagram_id` → `null`
- Conversation items: `body` → `[Contenido eliminado por solicitud GDPR]`
- Attachments físicos: borrados del storage

### Hard delete (admin con confirmación)

```
DELETE /panel/helpdesk/customers/{customer}/gdpr-delete?hard=1
```

Borra todo en cascada. Requiere confirmation token en el body.

### Limpieza periódica

Comando scheduled `helpdesk:purge-old-gdpr-deletes` (diario) hard-deletes los soft-deletes más viejos que **90 días**.

Configurable: `HELPDESK_GDPR_RETENTION_DAYS=90` en `.env`.

## 3. PII Masking

Service: `Modules\Helpdesk\Services\PiiMaskingService::mask($text)`.

Detecta y enmascara:
- **Tarjetas de crédito** (13-19 dígitos): `4532 1234 5678 9010` → `**** **** **** 9010`
- **DNI español** (8 dígitos + letra): `12345678X` → `*****678X`
- **Emails**: `juan@empresa.com` → `j***@empresa.com`
- **Teléfonos** (9-15 dígitos): `+34612345678` → `***5678`

**Aplicar en logs**:
```php
Log::error('Webhook fail', [
    'message' => PiiMaskingService::mask($e->getMessage()),
]);
```

## 4. 2FA TOTP para agentes

### Para agentes (voluntario)

1. Login normal
2. Visita `/2fa/setup`
3. Escanea QR con Google Authenticator / Authy
4. Confirma con primer código TOTP de 6 dígitos
5. Guarda los 10 códigos de recuperación en lugar seguro

### Para admins (forzado)

```env
HELPDESK_REQUIRE_2FA_FOR_ADMINS=true
```

Aplicado vía middleware `Require2FA` en el grupo de rutas del manager.
Cada agente con rol `admin` o `super-admin` se redirige a `/2fa/setup` si no tiene 2FA confirmado.

### Forzar para todos

```env
HELPDESK_REQUIRE_2FA=true
```

### En cada login

Después del login con email/password, si el usuario tiene `two_factor_confirmed_at`, se redirige a `/2fa/challenge` que pide TOTP. Sólo después de validarlo se marca la sesión `2fa_passed=true`.

### Recovery codes

Si el agente pierde el dispositivo:
- Cada recovery code es uno-uso
- Se invalida tras usarse
- El agente puede regenerar nuevos en `/2fa/setup`

### Endpoints

| Endpoint | Auth | Body | Descripción |
|---|---|---|---|
| `GET /2fa/setup` | web | — | Pantalla con QR + recovery codes |
| `POST /2fa/enable` | web | — | Genera secret + retorna QR base64 |
| `POST /2fa/confirm` | web | `{code: "123456"}` | Confirma con primer código |
| `POST /2fa/verify` | web | `{code: "123456" o recovery}` | Marca sesión `2fa_passed=true` |
| `POST /2fa/disable` | web | `{password: "current"}` | Desactiva 2FA tras confirmar password |

### Servicio

`Modules\Helpdesk\Services\Compliance\TwoFactorService` usa `pragmarx/google2fa`.

## 5. Audit Log + Compliance Trails

Toda acción sensible se registra en `helpdesk_audit_logs`:

| Action | Se loggea cuando |
|---|---|
| `customer.viewed` | Admin abre el customer detail |
| `customer.exported` | GDPR export ejecutado |
| `customer.deleted_soft` | GDPR soft delete |
| `customer.deleted_hard` | GDPR hard delete |
| `2fa.enabled` | Usuario activa 2FA |
| `2fa.disabled` | Usuario desactiva 2FA |
| `conversation.assigned` | Reasignación |
| `conversation.closed` | Cierre |

Retención recomendada: **90 días** (HIPAA / PCI requiere 1 año, configurable).

Para agentes con sospecha de mal uso:
```php
\Modules\Helpdesk\Models\AuditLog::query()
    ->where('user_id', $agentId)
    ->where('created_at', '>=', now()->subDays(7))
    ->where('action', 'like', 'customer.%')
    ->orderByDesc('created_at')
    ->get();
```

## 6. Variables `.env`

```env
# GDPR
HELPDESK_GDPR_RETENTION_DAYS=90

# 2FA
HELPDESK_REQUIRE_2FA=false
HELPDESK_REQUIRE_2FA_FOR_ADMINS=true

# Audit log
HELPDESK_AUDIT_RETENTION_DAYS=90
```

## 7. Comandos scheduled

| Comando | Frecuencia | Descripción |
|---|---|---|
| `helpdesk:purge-old-gdpr-deletes` | diario | Hard-delete clientes con soft-delete > 90 días |
| `helpdesk:prune-audit-logs` | semanal | Borra logs viejos según `HELPDESK_AUDIT_RETENTION_DAYS` |

## 8. Checklist de cumplimiento GDPR

- [ ] Política de privacidad publicada en webpage
- [ ] Cookies banner con consent
- [ ] DPO designado (Data Protection Officer)
- [ ] Registro de actividades de tratamiento (Art. 30)
- [x] Right of Access (Art. 15) — `gdpr-export` ✅
- [x] Right to Erasure (Art. 17) — `gdpr-delete` ✅
- [x] Data audit log (Art. 30) — `helpdesk_audit_logs` ✅
- [x] Data minimization en logs — `PiiMaskingService` ✅
- [ ] DPA con Meta firmado (necesario para procesar datos vía Messenger/IG/WA)
- [ ] DPA con OpenAI / DeepL / Slack firmado si usas esas integraciones

## 9. Test manual

```bash
# Soft delete
curl -X DELETE -H "Cookie: ..." \
  "https://system.test/panel/helpdesk/customers/123/gdpr-delete?hard=0"

# Export
curl -O -H "Cookie: ..." \
  "https://system.test/panel/helpdesk/customers/123/gdpr-export"

# Activar 2FA en tinker
php artisan tinker
>>> $svc = app(\Modules\Helpdesk\Services\Compliance\TwoFactorService::class);
>>> $result = $svc->enableForUser(\App\Models\User::find(1));
>>> // QR en $result['qr_code_url'], copia a authenticator app
>>> // Después en una nueva petición con el código del authenticator:
>>> $svc->confirmEnable(\App\Models\User::find(1), '123456');
```
