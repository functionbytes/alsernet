# Prueba de Acciones de Email de Documentos

## 📧 Acciones Disponibles

En la página de gestión de documentos, hay 5 acciones de email disponibles:

### 1. **Enviar Notificación Inicial** (Send Notification)
- **Endpoint:** `POST /administrative/documents/{uid}/send-notification`
- **Qué hace:** Envía email solicitando que el cliente cargue la documentación
- **Plantilla:** `document_request` (Solicitud de documentación)
- **Responsable:** `DocumentEmailTemplateService::sendInitialRequest()`

### 2. **Enviar Recordatorio** (Send Reminder)
- **Endpoint:** `POST /administrative/documents/{uid}/send-reminder`
- **Qué hace:** Envía email de recordatorio al cliente
- **Plantilla:** `document_reminder` (Recordatorio de documentación)
- **Variables:** Incluye `{DAYS_SINCE_REQUEST}`
- **Responsable:** `DocumentEmailTemplateService::sendReminder()`

### 3. **Enviar Documentos Faltantes** (Send Missing)
- **Endpoint:** `POST /administrative/documents/{uid}/send-missing`
- **Qué hace:** Solicita documentos específicos que faltan
- **Plantilla:** `document_missing` (Documentación faltante)
- **Parámetros:** `missing_docs[]`, `notes`
- **Responsable:** `DocumentEmailTemplateService::sendMissingDocuments()`

### 4. **Enviar Correo Personalizado** (Send Custom Email)
- **Endpoint:** `POST /administrative/documents/{uid}/send-custom-email`
- **Qué hace:** Envía un email totalmente personalizado
- **Plantilla:** Opcional (de settings: `documents.mail_template_custom_email_id`)
- **Parámetros:** `subject`, `content`
- **Responsable:** `DocumentEmailTemplateService::sendCustomEmail()`

### 5. **Reenviar Recordatorio** (Resend Reminder)
- **Endpoint:** `POST /administrative/documents/{uid}/resend-reminder`
- **Qué hace:** Similar a "Enviar Recordatorio" pero con validaciones adicionales
- **Plantilla:** `document_reminder`
- **Responsable:** `DocumentEmailTemplateService::sendReminder()`

---

## 🧪 Guía de Prueba Manual

### Requisitos Previos:
1. Acceso al panel administrativo
2. Un documento de prueba
3. Queue worker ejecutándose: `php artisan queue:work --queue=emails`
4. Email válido configurado en el documento

### Pasos de Prueba:

#### **Prueba 1: Enviar Notificación Inicial**

```
1. Ve a: /administrative/documents/manage/{uid}
2. Busca el botón "Enviar Notificación Inicial"
3. Haz clic en él
4. Verifica:
   - ✓ Mensaje: "Email de notificación en cola para envío"
   - ✓ El email debe recibir la solicitud de documentación
   - ✓ Verificar en DocumentAction que se registró como email_sent_initial_request
```

**Variables que deben estar reemplazadas:**
- `{CUSTOMER_NAME}` → Juan García (ejemplo)
- `{ORDER_REFERENCE}` → ORD-2025-001 (ejemplo)
- `{UPLOAD_LINK}` → URL de carga del documento

---

#### **Prueba 2: Enviar Recordatorio**

```
1. Ve a: /administrative/documents/manage/{uid}
2. Busca el botón "Enviar Recordatorio"
3. Haz clic en él
4. Verifica:
   - ✓ Mensaje: "Email de recordatorio en cola para envío"
   - ✓ El email debe recibir un recordatorio
   - ✓ Debe incluir {DAYS_SINCE_REQUEST} reemplazado con número real
```

**Variables que deben estar reemplazadas:**
- `{DAYS_SINCE_REQUEST}` → 5 (ejemplo)
- `{REMINDER_MESSAGE}` → Mensaje desde settings
- `{CUSTOMER_NAME}` → Juan García

---

#### **Prueba 3: Enviar Documentos Faltantes**

```
1. Ve a: /administrative/documents/manage/{uid}
2. Busca la sección "Enviar Email de Documentos Faltantes"
3. Selecciona los documentos faltantes (checkboxes)
4. Opcionalmente agrega notas
5. Haz clic en "Enviar Email"
6. Verifica:
   - ✓ Mensaje: "Email de solicitud en cola para envío"
   - ✓ El email debe incluir la lista de documentos faltantes
   - ✓ Las notas deben aparecer formateadas
```

**Variables especiales:**
- `{MISSING_DOCUMENTS}` → `<ul><li>Documento 1</li><li>Documento 2</li></ul>`
- `{REQUEST_REASON}` → Las notas que escribiste

---

#### **Prueba 4: Enviar Correo Personalizado**

```
1. Ve a: /administrative/documents/manage/{uid}
2. Busca la sección "Enviar Correo Personalizado"
3. Verifica:
   - ✓ El toggle "Habilitar correo personalizado" está ON
   - ✓ Hay una plantilla seleccionada en "Plantilla de Email (Opcional)"
4. Rellena:
   - Subject: "Test de correo personalizado"
   - Content: "Hola {CUSTOMER_NAME}, esto es un test"
5. Haz clic en "Enviar"
6. Verifica:
   - ✓ Mensaje: "Correo en cola para envío"
   - ✓ El email debe usar la plantilla seleccionada
   - ✓ Las variables deben estar reemplazadas
```

**Variables disponibles:**
- Todas las variables de documento: `{CUSTOMER_NAME}`, `{ORDER_ID}`, etc.
- Variables del sistema: `{SITE_NAME}`, `{SUPPORT_EMAIL}`, etc.

---

#### **Prueba 5: Reenviar Recordatorio**

```
1. Ve a: /administrative/documents/manage/{uid}
2. Busca el botón "Reenviar Recordatorio"
3. Haz clic en él
4. Verifica:
   - ✓ Funcionamiento idéntico a "Enviar Recordatorio"
```

---

## 🔍 Verificación Completa

Después de cada prueba, ejecuta:

```bash
# 1. Verificar que el job se despachó
php artisan queue:work --queue=emails

# 2. Verificar que se registró la acción
php artisan tinker
> \App\Models\Document\DocumentAction::latest()
  ->where('action_type', 'LIKE', 'email_%')
  ->first()

# 3. Verificar en logs
tail -20 storage/logs/laravel.log | grep -i "email\|custom"
```

---

## ✅ Checklist de Validación

Para cada acción de email, verifica:

- [ ] **Respuesta inmediata:** Mensajeapareció ("Email en cola...")
- [ ] **Job despachado:** Aparece en `queue:work`
- [ ] **Job procesado:** Sin errores en los logs
- [ ] **Email recibido:** Llega a la bandeja de entrada del cliente
- [ ] **Variables reemplazadas:** No aparecen placeholders como `{CUSTOMER_NAME}`
- [ ] **Plantilla aplicada:** Usa el layout correcto si hay plantilla
- [ ] **Acción registrada:** Aparece en DocumentAction table
- [ ] **Auditoría:** Se registra con admin ID y timestamp

---

## 🚨 Errores Comunes y Soluciones

### Error: "Email de notificación en cola pero nunca se envía"
**Causa:** Queue worker no está ejecutándose
**Solución:**
```bash
php artisan queue:work --queue=emails
```

### Error: "Variables no están siendo reemplazadas"
**Causa:** Las variables no están siendo pasadas correctamente
**Solución:**
```bash
# Verifica en logs:
grep "Error sending" storage/logs/laravel.log
```

### Error: "Plantilla personalizada no se está usando"
**Causa:** El template_id no está configurado correctamente
**Solución:**
```bash
php artisan tinker
> \App\Models\Setting::get('documents.mail_template_custom_email_id')
# Si está vacío, configura un ID válido
```

### Error: "Email dice 'Documentación cargada' pero debería ser otro"
**Causa:** Se está usando la plantilla por defecto en lugar de la personalizada
**Solución:**
- Ve a Settings → Documents → Configuración
- En "Correo Personalizado", selecciona la plantilla correcta
- Guarda

---

## 📊 Reporte de Prueba

Después de completar todas las pruebas, completa este reporte:

```
✓ Prueba 1 - Notificación Inicial: PASADA / FALLIDA
  Notas: ________________

✓ Prueba 2 - Recordatorio: PASADA / FALLIDA
  Notas: ________________

✓ Prueba 3 - Documentos Faltantes: PASADA / FALLIDA
  Notas: ________________

✓ Prueba 4 - Correo Personalizado: PASADA / FALLIDA
  Notas: ________________

✓ Prueba 5 - Reenviar Recordatorio: PASADA / FALLIDA
  Notas: ________________

Variables reemplazadas correctamente: SÍ / NO
Plantillas se aplican correctamente: SÍ / NO
Acciones se registran en auditoría: SÍ / NO
```

---

## 🎯 Resultado Esperado

Cuando todo está funcionando correctamente:

✓ **Respuesta inmediata:** Usuario ve "Email en cola para envío"
✓ **Procesamiento async:** El email se envía en background (sin bloquear)
✓ **Emails correctos:** Cada uno usa su plantilla y variables
✓ **Auditoría:** Todos los emails se registran en DocumentAction
✓ **Logs limpios:** Sin errores ni warnings relacionados

---

## 💾 Ejemplos de Respuestas Esperadas

### Respuesta exitosa:
```json
{
  "success": true,
  "message": "Email de notificación en cola para envío",
  "recipient": "cliente@example.com"
}
```

### Respuesta con error (no es culpa del código):
```json
{
  "success": false,
  "message": "No se pudo enviar: documento sin email de cliente",
  "document_email": null
}
```

---

Ejecuta estas pruebas y reporta cualquier anomalía.
