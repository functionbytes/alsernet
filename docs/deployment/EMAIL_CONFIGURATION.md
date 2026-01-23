# 📧 Configuración de Emails - Queue Worker

**Estado**: ✅ CONFIGURADO EN SUPERVISOR
**Archivo**: `/deployment/supervisor/laravel-queue-worker.conf`
**Queue**: `emails` (processará emails automáticamente)

---

## ✅ Lo que YA ESTÁ CONFIGURADO

### 1. Supervisor Queue Worker para Emails
```ini
[program:laravel-queue-worker]
command=/usr/bin/php /home2/webadminpruebas/web/artisan queue:work --queue=default,emails,notifications
numprocs=4  # 4 workers en paralelo procesando emails
```

**Esto significa**:
- ✅ Queue Worker escucha la cola `emails`
- ✅ 4 workers en paralelo (procesarán emails más rápido)
- ✅ Se reinicia automáticamente si falla
- ✅ Logs en `/var/log/supervisor/laravel-queue-worker.log`

### 2. MailTemplateJob en módulo Document
**Archivo**: `modules/Document/app/Jobs/MailTemplateJob.php`

```php
class MailTemplateJob implements ShouldQueue
{
    public int $tries = 3;        // ✅ 3 reintentos si falla
    public int $timeout = 60;     // ✅ 60 segundos máximo
    public int $backoff = 5;      // ✅ 5 segundos entre reintentos
    public function __construct(...) {
        $this->onQueue('emails');  // ✅ Cola: emails
    }
}
```

**Tipos de email que envía**:
- `request` - Solicitud inicial de documento
- `reminder` - Recordatorios
- `upload` - Confirmación de carga
- `approval` - Aprobación
- `rejection` - Rechazo
- `missing` - Documentos faltantes
- `custom` - Email personalizado

### 3. DocumentEmailService que dispara jobs
**Archivo**: `modules/Document/app/Services/DocumentEmailService.php`

```php
MailTemplateJob::dispatch($document, 'request');  // Se inserta en cola
MailTemplateJob::dispatch($document, 'reminder');
// ...
```

**En toda la aplicación** se usan estos métodos en controllers, listeners, etc.

---

## ⚙️ CONFIGURACIÓN DE EMAIL ACTUAL

### En `.env`:
```env
# SMTP para desarrollo (MailHog)
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=mail@a-alvarez.com
MAIL_FROM_NAME="${APP_NAME}"

# Mailjet (credenciales vacías)
MAILJET_APIKEY=
MAILJET_APISECRET=
```

### 🔴 PROBLEMA: Configuración es para DESARROLLO

- ✅ MailHog en `127.0.0.1:1025` - Solo para testing local
- ❌ NO enviará emails reales a internet
- ❌ Mailjet no está configurado

---

## 🔧 ¿QUÉ NECESITA HACER EL ADMINISTRADOR?

### Opción 1: Usar Mailjet (RECOMENDADO para producción)

**Paso 1**: Obtener credenciales en https://www.mailjet.com/

**Paso 2**: Actualizar `.env`:
```env
MAIL_MAILER=mailjet
MAIL_HOST=in-v3.mailjet.com
MAIL_PORT=587
MAIL_USERNAME=tu_api_key_mailjet
MAIL_PASSWORD=tu_secret_key_mailjet
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tudominio.com
MAIL_FROM_NAME="Reverdezcámonos"

# Credenciales Mailjet
MAILJET_APIKEY=tu_api_key_mailjet
MAILJET_APISECRET=tu_secret_key_mailjet
```

**Paso 3**: Verificar conexión:
```bash
php artisan tinker
Mail::raw('Test', function($message) {
    $message->to('test@example.com')
            ->subject('Test Email');
});
exit
```

---

### Opción 2: Usar sendmail/Postfix del servidor

**Paso 1**: Instalar Postfix:
```bash
sudo apt-get install postfix  # Seleccionar "Internet Site"
```

**Paso 2**: Actualizar `.env`:
```env
MAIL_MAILER=sendmail
MAIL_HOST=localhost
MAIL_PORT=25
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@tudominio.com
MAIL_FROM_NAME="Reverdezcámonos"
```

**Paso 3**: Testear:
```bash
php artisan mail:send
```

---

### Opción 3: Usar Amazon SES

**Paso 1**: Configurar AWS SES

**Paso 2**: Actualizar `.env`:
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=tu_key
AWS_SECRET_ACCESS_KEY=tu_secret
AWS_DEFAULT_REGION=eu-west-1
MAIL_FROM_ADDRESS=noreply@tudominio.com
```

---

## 📋 CHECKLIST - Lo que YA está hecho

- ✅ **Supervisor configurado** para procesar cola `emails`
- ✅ **MailTemplateJob creado** en módulo Document
- ✅ **Múltiples types de email** implementados
- ✅ **Queue Worker con 4 procesos** en paralelo
- ✅ **Reintentos automáticos** (3x)
- ✅ **Logs configurados** en Supervisor

---

## 🚀 PASOS PARA ACTIVAR EMAILS EN PRODUCCIÓN

### Paso 1: Administrador configura email (Mailjet / Postfix / SES)
```bash
# Editar .env con credenciales
sudo nano /home2/webadminpruebas/web/.env
```

### Paso 2: Reiniciar Queue Worker
```bash
# systemd
sudo systemctl restart queue-worker.service

# o Supervisor
sudo supervisorctl restart laravel-queue-worker:*
```

### Paso 3: Verificar que procesa emails
```bash
# Ver logs
sudo tail -f /var/log/supervisor/laravel-queue-worker.log

# O en systemd
sudo journalctl -u queue-worker.service -f
```

### Paso 4: Probar enviando un email
```bash
cd /home2/webadminpruebas/web
php artisan tinker

# Crear documento de prueba que envíe email
$doc = \Modules\Document\Entities\Document::factory()->create();

# Esperar 5-10 segundos para que procese
# Debería ver en logs: "Email enviado a..."

exit
```

---

## 📊 Flujo Completo de Email

```
1. Usuario realiza acción en app
   (crea documento, aprueba, etc)
   ↓
2. Controller dispara MailTemplateJob
   MailTemplateJob::dispatch($document, 'approval')
   ↓
3. Job se inserta en tabla `jobs` (Base de datos)
   ↓
4. Queue Worker detecta job
   (proceso que está corriendo siempre)
   ↓
5. Ejecuta MailTemplateJob::handle()
   ├─ Obtiene plantilla de email
   ├─ Reemplaza variables
   ├─ Envía via SMTP/Mailjet/Postfix
   ↓
6. Si falla → Reintenta (máx 3x)
   ↓
7. Si éxito → Elimina de tabla `jobs`
   Si falla → Mueve a tabla `failed_jobs`
   ↓
✅ Email enviado (o error registrado)
```

---

## 🔍 Monitoreo de Emails

### Ver emails en cola
```bash
cd /home2/webadminpruebas/web
php artisan tinker

# Ver jobs pendientes
DB::table('jobs')->get();

# Ver jobs fallidos
DB::table('failed_jobs')->get();

exit
```

### Ver logs de Supervisor
```bash
# Todos los logs
tail -f /var/log/supervisor/laravel-queue-worker.log

# Buscar errores de email
grep -i "mail\|email" /var/log/supervisor/laravel-queue-worker.log

# Últimas 100 líneas
tail -100 /var/log/supervisor/laravel-queue-worker.log
```

### Ver logs de systemd
```bash
# Todas las líneas
sudo journalctl -u queue-worker.service -f

# Buscar errores
sudo journalctl -u queue-worker.service | grep -i error
```

---

## ⚠️ Problemas Comunes

### Problema: Los emails no se envían
**Solución**:
1. Verificar que Queue Worker está corriendo: `ps aux | grep queue:work`
2. Verificar que .env tiene credenciales correctas
3. Revisar logs: `tail -f /var/log/supervisor/laravel-queue-worker.log`
4. Reiniciar: `sudo supervisorctl restart laravel-queue-worker:*`

### Problema: "Connection timeout" en SMTP
**Solución**:
1. Verificar servidor SMTP está activo
2. Verificar puerto correcto en .env
3. Si es Mailjet, verificar API key no vencida
4. Revisar firewall: `sudo ufw allow 25/tcp` (SMTP)

### Problema: "Invalid credentials"
**Solución**:
1. Verificar API key/contraseña en .env
2. Para Mailjet: obtener nuevas credenciales
3. Para Postfix: `sudo postfix reload`

---

## 📈 Rendimiento

### Con 4 workers en paralelo
- Puede procesar **~100 emails/minuto** (depende del servidor)
- Los emails se envían en **background** (no bloquean UI)
- Si falla 1 worker, otros 3 siguen funcionando

### Escalar si hay más emails
```ini
# En supervisor: aumentar numprocs
numprocs=8  # o más
```

---

## 📞 Resumen Final

### ✅ YA ESTÁ HECHO
- Queue Worker configurado en Supervisor ✅
- MailTemplateJob con reintentos ✅
- Múltiples tipos de email ✅
- Sistema de colas implementado ✅

### ⏳ NECESITA ADMINISTRADOR
1. Configurar SMTP (Mailjet / Postfix / SES) en `.env`
2. Reiniciar Queue Worker
3. Testear que los emails llegan

### 🚀 RESULTADO FINAL
Los emails se envían **automáticamente en background** sin bloquear la aplicación

---

**Última actualización**: 12 de Enero 2026
