# Configuración de Correo Personalizado - Guía de Troubleshooting

## 📋 Checklist de Verificación

### 1. ¿Está habilitado el correo personalizado?
```bash
php artisan tinker
> \App\Models\Setting::get('documents.enable_custom_email')
# Debería retornar: 'yes'
```

**Si no está habilitado:**
- Ve a: `/manager/settings/documents/configurations`
- En la sección "Correo Personalizado"
- Marca el checkbox "Habilitar correo personalizado"
- Guarda los cambios

### 2. ¿Está seleccionada una plantilla?
```bash
php artisan tinker
> $templateId = \App\Models\Setting::get('documents.mail_template_custom_email_id')
> echo "Template ID: " . ($templateId ? $templateId : 'ninguna')

# Si hay un ID, verificar que existe:
> \App\Models\Mail\MailTemplate::find($templateId)
```

**Si no hay plantilla o no existe:**
- Ve a: `/manager/settings/documents/configurations`
- En la sección "Correo Personalizado"
- Selecciona una plantilla en "Plantilla de Email (Opcional)"
- Las plantillas disponibles son:
  - `Documentación cargada` (document_confirmation)
  - `Solicitud de documentación` (document_request)
  - `Recordatorio de documentación` (document_reminder)
  - Y otras configuradas en el sistema

### 3. ¿Está habilitada la plantilla?
```bash
php artisan tinker
> $template = \App\Models\Mail\MailTemplate::find(23)  # Usa el ID que obtuviste arriba
> echo "¿Habilitada?: " . ($template->is_enabled ? 'Sí' : 'No')
> echo "Módulo: " . $template->module
```

**Si la plantilla está deshabilitada:**
- La plantilla debe estar en el módulo `documents`
- Debe tener `is_enabled = 1` en la BD

### 4. ¿Está funcionando el job?

Cuando envías un correo personalizado:

1. **Se despacha un job:**
   ```php
   SendMailTemplateJob::dispatch($document, 'custom', [
       'subject' => 'Mi asunto',
       'content' => 'Mi contenido',
       'template_id' => 23,  // ← La plantilla seleccionada
   ]);
   ```

2. **Se procesa en background:**
   ```bash
   # En una terminal, ejecuta:
   php artisan queue:work --queue=emails

   # O usa Horizon:
   php artisan horizon
   ```

3. **Se registra en DocumentAction:**
   ```bash
   php artisan tinker
   > \App\Models\Document\DocumentAction::latest()->first()
   # Verifica que el action_type sea 'email_sent_custom'
   ```

---

## 🔍 Diagnóstico Completo

Ejecuta este script para obtener un diagnóstico completo:

```bash
php artisan tinker <<'EOF'
echo "=== DIAGNÓSTICO DE CORREO PERSONALIZADO ===\n";

// 1. ¿Está habilitado?
$enabled = \App\Models\Setting::get('documents.enable_custom_email') === 'yes';
echo "1. Correo personalizado habilitado: " . ($enabled ? '✓ SÍ' : '✗ NO') . "\n";

// 2. ¿Hay plantilla seleccionada?
$templateId = \App\Models\Setting::get('documents.mail_template_custom_email_id');
echo "2. Template ID configurado: " . ($templateId ? '✓ ID: ' . $templateId : '✗ NINGUNO') . "\n";

// 3. ¿Existe la plantilla en BD?
if ($templateId) {
    $template = \App\Models\Mail\MailTemplate::find($templateId);
    if ($template) {
        echo "   - Nombre: {$template->name}\n";
        echo "   - Clave: {$template->key}\n";
        echo "   - Módulo: {$template->module}\n";
        echo "   - Habilitada: " . ($template->is_enabled ? 'SÍ' : 'NO') . "\n";
        echo "   - Tiene traducciones: " . ($template->translations()->count() > 0 ? 'SÍ' : 'NO') . "\n";
    } else {
        echo "   ✗ PLANTILLA NO ENCONTRADA EN BD\n";
    }
}

// 4. ¿Hay jobs pendientes?
$pendingJobs = \App\Jobs\Document\SendMailTemplateJob::count();
echo "\n3. Jobs de email pendientes: {$pendingJobs}\n";

// 5. Plantillas disponibles
echo "\n4. Plantillas disponibles en el sistema:\n";
$templates = \App\Models\Mail\MailTemplate::where('module', 'documents')
    ->where('is_enabled', true)
    ->get();
foreach ($templates as $t) {
    echo "   - ID: {$t->id}, Nombre: {$t->name}, Key: {$t->key}\n";
}

exit();
EOF
```

---

## 🐛 Problemas Comunes

### Problema: "El correo se envía pero no usa la plantilla"

**Causa:** El `template_id` podría ser `null` o `0`

**Solución:**
```bash
# Verifica que el setting está configurado
php artisan tinker
> \App\Models\Setting::get('documents.mail_template_custom_email_id')

# Si está vacío, configúralo manualmente:
> \App\Models\Setting::set('documents.mail_template_custom_email_id', '23')
```

### Problema: "Aparece error 'Plantilla no encontrada'"

**Causa:** El ID en el setting no existe en la tabla `mail_templates`

**Solución:**
```bash
# Lista plantillas disponibles:
php artisan tinker
> \App\Models\Mail\MailTemplate::where('module', 'documents')
  ->where('is_enabled', true)
  ->select('id', 'name', 'key')
  ->get()

# Guarda un ID válido:
> \App\Models\Setting::set('documents.mail_template_custom_email_id', 'ID_VALIDO')
```

### Problema: "El job no se procesa"

**Causa:** El queue worker no está ejecutándose

**Solución:**
```bash
# En una terminal, ejecuta:
php artisan queue:work --queue=emails

# O verifica el estado de Supervisor:
sudo supervisorctl status laravel-worker
```

---

## 📧 Flujo Completo de Envío

```
1. Usuario selecciona "Enviar Correo Personalizado"
   ↓
2. Admin rellena:
   - Subject: "Mi asunto personalizado"
   - Content: "Mi contenido personalizado"
   ↓
3. Controller obtiene el template ID del setting
   ✓ documents.mail_template_custom_email_id = 23
   ↓
4. Se despacha SendMailTemplateJob con:
   - document
   - email_type: 'custom'
   - emailData: { subject, content, template_id: 23 }
   ↓
5. Job se encola en la cola 'emails'
   ↓
6. Queue worker procesa el job:
   → Llama DocumentEmailTemplateService::sendCustomEmail()
   → Si template_id=23, usa la plantilla "Documentación cargada"
   → Reemplaza variables {CUSTOMER_NAME}, {ORDER_ID}, etc.
   → Aplica layout de la plantilla
   → Envía el email
   ↓
7. Se registra en DocumentAction
   ↓
8. ✓ Email enviado exitosamente
```

---

## ✨ Verificación Manual

Para verificar que todo está funcionando:

1. Ve a `/administrative/documents/{uid}`
2. Busca la sección "Enviar Correo Personalizado"
3. Rellena:
   - Subject: "Test de correo personalizado"
   - Content: "Este es un test con {CUSTOMER_NAME}"
4. Haz clic en "Enviar"
5. Verifica que aparece: "Correo en cola para envío"
6. En otra terminal, ejecuta: `php artisan queue:work --queue=emails`
7. El email debería ser enviado con la plantilla configurada

---

## 🔧 Configuración Manual en BD

Si necesitas configurar manualmente:

```sql
-- Ver la configuración actual
SELECT * FROM settings WHERE `key` = 'documents.mail_template_custom_email_id';
SELECT * FROM settings WHERE `key` LIKE 'documents.%email%';

-- Configurar un template
UPDATE settings
SET value = '23'
WHERE `key` = 'documents.mail_template_custom_email_id';

-- Habilitar correo personalizado
UPDATE settings
SET value = 'yes'
WHERE `key` = 'documents.enable_custom_email';
```

---

## 📞 Soporte

Si tienes problemas:
1. Ejecuta el diagnóstico completo arriba
2. Verifica los logs en: `storage/logs/laravel.log`
3. Busca errores con: `grep "custom_email" storage/logs/laravel.log`
4. Verifica que el queue worker está corriendo
