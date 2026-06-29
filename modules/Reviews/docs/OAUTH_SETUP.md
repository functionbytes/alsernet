# Configuración OAuth con Google Business Profile

Guía paso a paso para obtener credenciales de Google Cloud y configurar la integración OAuth 2.0 con Google Business Profile API.

## Tabla de Contenidos

1. [Requisitos Previos](#requisitos-previos)
2. [Crear Proyecto en Google Cloud](#crear-proyecto-en-google-cloud)
3. [Habilitar APIs Requeridas](#habilitar-apis-requeridas)
4. [Configurar Pantalla de Consentimiento OAuth](#configurar-pantalla-de-consentimiento-oauth)
5. [Crear Credenciales OAuth 2.0](#crear-credenciales-oauth-20)
6. [Configurar Laravel](#configurar-laravel)
7. [Prueba de Conexión](#prueba-de-conexión)
8. [Troubleshooting](#troubleshooting)
9. [Renovación de Tokens](#renovación-de-tokens)
10. [Seguridad](#seguridad)

---

## Requisitos Previos

Antes de comenzar, asegúrate de tener:

- Cuenta de Google activa
- Acceso a [Google Cloud Console](https://console.cloud.google.com)
- Google Business Profile creado y verificado
- Rol de propietario o administrador en la ubicación de negocio
- Dominio configurado con HTTPS en producción (recomendado)

### Verificar Google Business Profile

1. Ir a [Google Business Profile](https://www.google.com/business)
2. Seleccionar la ubicación
3. Verificar que el estado es "Verificado"
4. Anotar el nombre de la ubicación (lo necesitarás después)

---

## Crear Proyecto en Google Cloud

### Paso 1: Acceder a Google Cloud Console

1. Ir a [Google Cloud Console](https://console.cloud.google.com)
2. Si ves un proyecto existente, puedes usarlo o crear uno nuevo
3. Aceptar términos de servicio si es primera vez

### Paso 2: Crear Proyecto Nuevo

1. Hacer click en el selector de proyecto (esquina superior izquierda)
2. Click en botón **"NEW PROJECT"** o **"CREATE PROJECT"**
3. Completar:
   - **Project name**: `Reviews Integration` (o tu nombre)
   - **Organization**: Dejar vacío si no tienes organización
4. Click en **"CREATE"**
5. Esperar 1-2 minutos a que se cree el proyecto

### Paso 3: Seleccionar el Proyecto

1. Una vez creado, seleccionarlo en el selector de proyecto
2. Verificar que aparece el nombre en la esquina superior izquierda

---

## Habilitar APIs Requeridas

Las siguientes 3 APIs **DEBEN** estar habilitadas para que funcione correctamente la integración:

1. **My Business Account Management API**
2. **My Business Information API**
3. **My Business API (v4)** - Versión legacy para reseñas

### Habilitar My Business Account Management API

1. Ir a [APIs & Services > Library](https://console.cloud.google.com/apis/library)
2. En buscador, escribir: `My Business Account Management`
3. Hacer click en el primer resultado
4. Click en botón **"ENABLE"**
5. Esperar confirmación (aparecerá "API enabled")

### Habilitar My Business Information API

1. Volver a [APIs & Services > Library](https://console.cloud.google.com/apis/library)
2. Buscar: `My Business Information`
3. Click en resultado
4. Click en **"ENABLE"**
5. Esperar confirmación

### Habilitar My Business API (v4)

1. Volver a [APIs & Services > Library](https://console.cloud.google.com/apis/library)
2. Buscar: `My Business API`
3. **IMPORTANTE**: Seleccionar versión **v4** (no v4.1)
4. Click en **"ENABLE"**
5. Esperar confirmación

### Verificar APIs Habilitadas

1. Ir a [APIs & Services > Enabled APIs & services](https://console.cloud.google.com/apis/dashboard)
2. Verificar que aparecen las 3 APIs listadas

---

## Configurar Pantalla de Consentimiento OAuth

La pantalla de consentimiento es lo que ven los usuarios cuando autorizan tu aplicación.

### Paso 1: Acceder a Pantalla de Consentimiento

1. Ir a [APIs & Services > OAuth consent screen](https://console.cloud.google.com/apis/consent)
2. Seleccionar tipo de usuario: **External** (a menos que sea empresa con Google Workspace)
3. Click en **"CREATE"**

### Paso 2: Información de la Aplicación

En sección **"App information"**, completar:

- **App name**: `Reviews Integration` (o nombre de tu app)
- **User support email**: tu email o email de soporte (visible para usuarios)
- **App logo** (opcional): Imagen 120x120px

En sección **"Developer contact information"**, ingresar:

- **Email addresses**: Tu email de desarrollador

Click en **"SAVE AND CONTINUE"**

### Paso 3: Definir Scopes OAuth

En sección **"Scopes"**:

1. Click en **"ADD OR REMOVE SCOPES"**
2. Buscar y seleccionar:
   - `https://www.googleapis.com/auth/business.manage` (requerido para reseñas)
   - `openid` (requerido)
   - `email` (requerido)
3. Click en **"UPDATE"**
4. Click en **"SAVE AND CONTINUE"**

**Nota sobre Scopes**: La aplicación solicita acceso para:
- Gestionar cuentas de negocio (`business.manage`)
- Ver email del usuario (`email`)
- Identificar usuario (`openid`)

### Paso 4: Agregar Usuarios de Prueba

En sección **"Test users"** (si usas OAuth en test mode):

1. Click en **"ADD USERS"**
2. Agregar emails de prueba (tus emails de Google)
3. Click en **"ADD"**
4. Click en **"SAVE AND CONTINUE"**

**Nota**: En producción, cualquier usuario puede autorizar. En test mode, solo estos usuarios.

---

## Crear Credenciales OAuth 2.0

### Paso 1: Ir a Credenciales

1. Ir a [APIs & Services > Credentials](https://console.cloud.google.com/apis/credentials)
2. Si aparece advertencia sobre "consent screen", hacer click en "Configure Consent Screen" y completar paso anterior
3. Click en **"+ CREATE CREDENTIALS"**
4. Seleccionar **"OAuth 2.0 Client ID"**

### Paso 2: Seleccionar Tipo de Aplicación

1. En dropdown **"Application type"**, seleccionar: **Web application**
2. En campo **"Name"**, ingresar: `Reviews Integration Web` (o similar)

### Paso 3: Configurar URIs Autorizadas

#### Authorized JavaScript origins

Estos son los dominios desde donde se hace la solicitud OAuth:

1. Click en **"ADD URI"** bajo "Authorized JavaScript origins"
2. Agregar:

```
https://tu-dominio.com
https://staging.tu-dominio.com
http://localhost:8000
http://127.0.0.1
```

**Ejemplo real**:
```
https://miempresa.com
http://localhost:8000
```

#### Authorized redirect URIs

Esta es la URL donde Google redirige después de autorizar. **MUY IMPORTANTE**: Debe ser exacta:

1. Click en **"ADD URI"** bajo "Authorized redirect URIs"
2. Agregar para cada entorno:

```
https://tu-dominio.com/settings/reviews/oauth/callback
https://staging.tu-dominio.com/settings/reviews/oauth/callback
http://localhost:8000/settings/reviews/oauth/callback
```

**Ejemplo real**:
```
https://miempresa.com/settings/reviews/oauth/callback
http://localhost:8000/settings/reviews/oauth/callback
```

3. Click en **"CREATE"**

### Paso 4: Copiar Credenciales

Se abrirá un modal mostrando:

```
Client ID: 1234567890-abcdefghijklmnopqrst.apps.googleusercontent.com
Client Secret: GOCSPX-abcdefghijklmnopqrst
```

**IMPORTANTE**:
- Copiar ambos valores exactamente (incluyendo puntos)
- El Client Secret no se mostrará nuevamente - si lo pierdes, elimina y crea nuevo
- Nunca compartir el Client Secret públicamente
- No commitear a git - solo usar en `.env`

Copiar a `.env`:

```env
GOOGLE_CLIENT_ID=1234567890-abcdefghijklmnopqrst.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abcdefghijklmnopqrst
```

---

## Configurar Laravel

### Paso 1: Variables de Entorno

Editar archivo `.env` del proyecto y agregar:

```env
# Google OAuth Credentials (de Google Cloud Console)
GOOGLE_CLIENT_ID=tu-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=tu-client-secret

# URL de la aplicación (debe coincidir con JavaScript origins)
APP_URL=https://tu-dominio.com
APP_ENV=production

# Configuración de sincronización de reseñas
REVIEWS_SYNC_INTERVAL=15              # Minutos entre sincronizaciones
REVIEWS_AUTO_PUBLISH=false            # No auto-publicar respuestas
REVIEWS_DEFAULT_VISIBLE=true          # Reseñas visibles por defecto
```

**Nota sobre APP_URL**:
- Debe ser HTTPS en producción
- Debe coincidir con "Authorized JavaScript origins" en Google Cloud
- Usado para generar redirect URI automáticamente

### Paso 2: Verificar Configuración

Ejecutar tinker para verificar que los valores se cargan correctamente:

```bash
php artisan tinker
```

Luego ejecutar:

```php
config('reviews.google.client_id')
config('reviews.google.client_secret')
config('reviews.google.redirect_uri')
```

Debe devolver:

```
"tu-client-id.apps.googleusercontent.com"
"tu-client-secret"
"https://tu-dominio.com/settings/reviews/oauth/callback"
```

Si los valores son NULL, revisar:
- `.env` tiene los valores correctos
- `.env` tiene formato correcto (sin espacios alrededor de =)
- Ejecutar `php artisan config:clear` para limpiar cache

### Paso 3: Verificar Archivo de Configuración

El archivo `modules/Reviews/config/google.php` carga automáticamente las variables de `.env`:

```php
<?php
return [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/settings/reviews/oauth/callback'),
    'scopes' => [
        'openid',
        'email',
        'https://www.googleapis.com/auth/business.manage',
    ],
];
```

No es necesario modificar este archivo si usas valores por defecto.

---

## Scopes Requeridos

La aplicación solicita los siguientes scopes de OAuth:

| Scope | Descripción | Razón |
|-------|-------------|-------|
| `openid` | Autenticación OpenID | Identificar usuario de forma única |
| `email` | Acceso al email | Ver email asociado a la cuenta Google |
| `https://www.googleapis.com/auth/business.manage` | Gestión de Business Profile | Sincronizar reseñas, publicar respuestas, gestionar ubicaciones |

**Importante**: El usuario debe autorizar todos los scopes para que funcione. Si rechaza alguno, habrá error.

---

## Prueba de Conexión

### Paso 1: Acceder al Panel

1. Asegurar que estás logged in a la aplicación
2. Ir a `/settings/reviews/connections`
3. Hacer click en botón **"Conectar Cuenta Google"** o **"Connect Google Account"**

### Paso 2: Flujo OAuth

El navegador redirigirá a Google donde:

1. Mostrar pantalla de login (si no estás logged in)
2. Mostrar pantalla de consentimiento con los scopes solicitados
3. Permitir o rechazar permisos

### Paso 3: Verificar Conexión Exitosa

Si todo va bien:

1. Google redirige de vuelta a tu aplicación
2. Aparece mensaje de éxito: "Conexión exitosa"
3. Nueva conexión aparece en la lista con estado "Active"
4. Email de Google aparece mostrado

Si hay error, ir a sección [Troubleshooting](#troubleshooting).

### Paso 4: Sincronizar Ubicaciones

1. Ir a `/settings/reviews/locations`
2. Click en **"Sincronizar Ubicaciones"**
3. Esperar a que se descarguen las ubicaciones de Google Business Profile
4. Seleccionar las ubicaciones que quieres sincronizar
5. Cambiar estado a "Active" para cada ubicación
6. Guardar

Las reseñas empezarán a sincronizarse automáticamente en los próximos 15 minutos.

---

## Troubleshooting

### Error: "Invalid Client ID"

**Síntomas**:
- Error durante login OAuth
- "Client ID is invalid"

**Causas**:
- Client ID mal copiado
- Caracteres extra/faltantes

**Solución**:

```bash
# 1. Verificar archivo .env
grep GOOGLE_CLIENT_ID .env

# 2. Ir a Google Cloud Console y copiar nuevamente
# https://console.cloud.google.com/apis/credentials

# 3. Verificar que incluye .apps.googleusercontent.com
# Correcto: 1234567890-abcdef.apps.googleusercontent.com
# Incorrecto: 1234567890-abcdef (sin .apps.googleusercontent.com)

# 4. Limpiar cache de configuración
php artisan config:clear

# 5. Verificar nuevamente
php artisan tinker
> config('reviews.google.client_id')
```

---

### Error: "Redirect URI mismatch"

**Síntomas**:
- Error: "The redirect_uri parameter doesn't match"
- "redirect_uri mismatch"

**Causa**: La URL a la que Google intenta redirigir no coincide exactamente con la configurada en Google Cloud Console.

**Solución**:

1. Verificar `.env`:

```bash
grep APP_URL .env
# Debe ser: APP_URL=https://tu-dominio.com (sin path)
```

2. Verificar que la URL es HTTPS en producción, HTTP en desarrollo

3. En Google Cloud Console ([Credentials](https://console.cloud.google.com/apis/credentials)):
   - Editar el OAuth Client
   - Agregar todas las variantes:
     ```
     https://tu-dominio.com/settings/reviews/oauth/callback
     http://localhost:8000/settings/reviews/oauth/callback
     ```
   - Las URLs deben ser exactas (protocolo, dominio, path)

4. Esperar 5 minutos para que Google procese cambios

5. Limpiar cache y reintentar:

```bash
php artisan config:clear
```

**Nota**: Las URLs en Google Cloud deben ser exactas - si usas https en uno y http en otro, debe estar ambas configuradas.

---

### Error: "Invalid OAuth state"

**Síntomas**:
- Error: "invalid state parameter"
- Sesión expire durante OAuth

**Causa**: El token CSRF o sesión expiró entre el paso 1 (solicitar auth) y paso 2 (callback).

**Solución**:

```bash
# 1. Limpiar sesión
php artisan cache:clear
php artisan session:table

# 2. Verificar que las sesiones se guardan correctamente
grep SESSION_DRIVER .env
# Debe ser: SESSION_DRIVER=database o SESSION_DRIVER=redis

# 3. Si usas Redis, verificar que Redis está corriendo
redis-cli ping
# Respuesta: PONG

# 4. Si usas database, verificar que tabla sessions existe
php artisan migrate --table=sessions

# 5. Intentar nuevamente
```

**Para Development**: Cambiar a database sessions si Redis causa problemas:

```env
SESSION_DRIVER=database
```

---

### Error: "Access blocked by administrator"

**Síntomas**:
- "Access blocked by administrator"
- Email de Google Workspace

**Causa**: Tu empresa tiene políticas de seguridad que bloquean apps OAuth no autorizadas.

**Solución**:

Contactar con administrador de Google Workspace para:

1. Aprobar la app OAuth
2. Hacer whitelist del dominio
3. Permitir acceso a Google Business Profile API

---

### Error: "User not authorized"

**Síntomas**:
- "The user is not authorized to perform this operation"
- "User has insufficient privileges"

**Causa**: El usuario Google no es propietario o manager de la ubicación en Google Business Profile.

**Solución**:

1. Ir a [Google Business Profile](https://www.google.com/business)
2. Seleccionar la ubicación
3. Ir a "Users" o "Usuarios"
4. Agregar el usuario si no está
5. Asegurar que tiene rol "Manager" o superior
6. Desconectar y reconectar OAuth en la app

---

### Error: "The user is not authenticated" al conectar

**Síntomas**:
- Error durante OAuth con usuario no logged in
- Redirección a login

**Solución**:

1. Hacer login a la aplicación antes de conectar Google
2. Verificar que sesión es válida:

```bash
php artisan tinker
> auth()->user()
# Debe devolver objeto User, no null
```

---

### Las ubicaciones no aparecen después de conectar

**Síntomas**:
- Conexión OAuth exitosa
- Pero al ir a sincronizar ubicaciones, lista vacía

**Causa**: Las ubicaciones no están creadas o verificadas en Google Business Profile.

**Solución**:

1. Ir a [Google Business Profile](https://www.google.com/business)
2. Crear ubicación si no existe
3. Completar verificación:
   - Ingresar dirección exacta
   - Seleccionar método de verificación (SMS, correo, etc)
   - Seguir instrucciones de Google
4. Esperar a que Google sincronice (hasta 48 horas)
5. En la app, ir a `/settings/reviews/locations`
6. Click en **"Sincronizar Ubicaciones"** nuevamente

---

### Token expira muy rápido o "Token expired" constantemente

**Síntomas**:
- Reseñas no se sincronizan
- Error "Access token has expired" a los pocos minutos
- Parece renovarse pero vuelve a expirar

**Causa**: El refresh token no fue guardado en la base de datos o configuración incorrecta.

**Solución**:

1. Verificar en base de datos:

```bash
php artisan tinker
> Modules\Reviews\Models\ReviewGoogleConnection::first()
```

Revisar que tenga campos:
- `access_token`: Valor (no null)
- `refresh_token`: Valor (no null) - **CRÍTICO**
- `token_expires_at`: Fecha futura

2. Si refresh_token es null, **RECONECTAR**:
   - Ir a `/settings/reviews/connections`
   - Click "Revoke" o eliminar conexión
   - Reconectar Google account
   - Aceptar todos los permisos nuevamente
   - El refresh_token se guardará correctamente

3. El refresh automático se ejecuta:
   - Cada vez que se usa el token (sincronización de reseñas)
   - Automáticamente via comando: `php artisan reviews:cleanup-expired`

---

### Reseñas no se sincronizan

**Síntomas**:
- Conexión OAuth exitosa
- Ubicaciones sincronizadas
- Pero reseñas no aparecen o no se actualizan

**Causas posibles**:
- Queue worker no está corriendo
- Job fallido en cola
- Sincronización deshabilitada

**Solución**:

1. Verificar que queue worker está corriendo:

```bash
# Ver jobs fallidos
php artisan queue:failed

# Reintentar jobs fallidos
php artisan queue:retry all

# En desarrollo, escuchar cola
php artisan queue:listen --queue=google-sync

# En producción, usar Supervisor (ver archivo queue.conf abajo)
```

2. Sincronizar manualmente:

```bash
php artisan reviews:sync
php artisan reviews:sync --location=1
php artisan reviews:sync --force
```

3. Verificar que sincronización está habilitada:

```bash
php artisan tinker
> config('reviews.general.auto_sync')
# Debe ser true
```

4. Para producción, configurar Supervisor:

```bash
sudo nano /etc/supervisor/conf.d/reviews-queue.conf
```

Agregar:

```conf
[program:reviews-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/a/artisan queue:work --queue=google-sync --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/reviews-queue.log
stopasgroup=true
killasgroup=true
```

Luego:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reviews-queue:*
sudo supervisorctl status
```

---

## Renovación de Tokens

### Token Lifecycle

| Token | Duración | Auto-renovación |
|-------|----------|-----------------|
| Access Token | 1 hora | Automática |
| Refresh Token | Indefinido | N/A |
| Credenciales OAuth | Indefinido | Manual si cambias secret |

### Auto-renovación (Automática)

La renovación de tokens ocurre automáticamente:

1. **Antes de cada sincronización**: Si token expira en próxima hora
2. **Via comando**: `php artisan reviews:cleanup-expired` (ejecutar cada 15 min)
3. **Via scheduler**: Si configuras `schedule()` en Laravel

No hay acción requerida del usuario.

### Renovación Manual

Si necesitas renovar token manualmente:

```bash
php artisan tinker
> Modules\Reviews\Models\ReviewGoogleConnection::find(1)->refreshTokenIfNeeded()
```

O renovar todos:

```bash
php artisan reviews:cleanup-expired
```

### Revocar Acceso Permanentemente

Para desconectar una cuenta Google:

1. En `/settings/reviews/connections`, click **"Revocar"**
2. O via Tinker:

```bash
php artisan tinker
> Modules\Reviews\Models\ReviewGoogleConnection::find(1)->markAsRevoked()
```

Esto:
- Revoca el token en Google
- Marca la conexión como revocada en BD
- **NO** elimina las reseñas ya sincronizadas
- **NO** elimina historial

---

## Seguridad

### Mejores Prácticas

1. **Nunca commitear credenciales**:
   - Usar `.env` para valores locales
   - Usar `.env.example` sin valores reales
   - Agregar `.env` a `.gitignore`

2. **Encriptación automática**:
   - Tokens OAuth se guardan encriptados en BD
   - Usa `APP_KEY` en `.env`
   - Cambiar `APP_KEY` invalidará tokens (requiere reconectar)

3. **HTTPS obligatorio en producción**:
   - Siempre usar HTTPS
   - Certificado SSL válido (Let's Encrypt recomendado)
   - Redirigir HTTP → HTTPS

4. **Rate Limiting**:
   - API tiene límite 60 requests/min
   - Google API tiene límites propios
   - Ver header `X-RateLimit-*` en respuestas

5. **Auditoría**:
   - Todas las acciones se registran en activity log
   - Ver histórico en base de datos: tabla `activity_log`
   - Revisar regularmente para conexiones sospechosas

6. **Rotación de Secrets** (Producción):
   - Cambiar `GOOGLE_CLIENT_SECRET` cada 6 meses
   - Generar nuevo en Google Cloud Console
   - Actualizar en `.env` y variables de entorno
   - No requiere reconectar usuarios (refresh token sigue siendo válido)

### Revocación de Acceso

En Google Cloud Console puedes revocar acceso en cualquier momento:

1. Ir a [Google Account > Security](https://myaccount.google.com/security)
2. Ir a "Third-party apps & services"
3. Encontrar "Reviews Integration"
4. Click en "Remove access"

Esto revoca **todos** los tokens generados para tu cuenta.

### Seguridad de Scopes

La app solicita solo los scopes necesarios:

- `openid` - Necesario para autenticación
- `email` - Necesario para identificar usuario
- `business.manage` - Necesario para reseñas

No solicita acceso a:
- Emails de usuarios
- Contraseñas
- Información personal
- Archivos o drive

---

## Referencias y Recursos

- [Google Business Profile API Docs](https://developers.google.com/my-business)
- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Google Cloud Console](https://console.cloud.google.com)
- [Google API Explorer](https://developers.google.com/apis-explorer)
- [Google Business Profile Verification](https://support.google.com/business/answer/7107242)

---

## Contacto y Soporte

Para problemas con Google OAuth que no están en esta guía:

- [Google Business Profile Support](https://support.google.com/business)
- [Google Cloud Support](https://cloud.google.com/support)
- Crear issue en repositorio con detalles del problema
