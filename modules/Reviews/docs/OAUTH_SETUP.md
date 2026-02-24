# Configuración OAuth con Google Business Profile

Guía paso a paso para obtener credenciales de Google Cloud y configurar la integración.

## 1. Crear Proyecto en Google Cloud Console

### Paso 1.1: Acceder a Google Cloud Console

1. Ir a [Google Cloud Console](https://console.cloud.google.com)
2. Si no tienes cuenta, crear una con tu email de Google
3. Aceptar términos de servicio

### Paso 1.2: Crear Proyecto Nuevo

1. Hacer click en selector de proyecto (esquina superior izquierda)
2. Click en "NEW PROJECT"
3. Ingresar nombre: `Reviews Integration` (o tu nombre)
4. Click en "CREATE"
5. Esperar a que se cree el proyecto (1-2 minutos)

### Paso 1.3: Seleccionar Proyecto

1. En el selector de proyecto, seleccionar el proyecto recién creado
2. Esperar a que cargue el dashboard

## 2. Habilitar APIs Requeridas

Las siguientes APIs deben estar habilitadas:

- My Business Account Management API
- My Business Information API
- My Business API (versión legacy v4)

### Paso 2.1: Habilitar My Business Account Management API

1. Ir a [APIs & Services > Library](https://console.cloud.google.com/apis/library)
2. En search box, buscar: `My Business Account Management API`
3. Hacer click en resultado
4. Click en "ENABLE"
5. Esperar confirmación

### Paso 2.2: Habilitar My Business Information API

1. Volver a [APIs & Services > Library](https://console.cloud.google.com/apis/library)
2. Buscar: `My Business Information API`
3. Click en resultado
4. Click en "ENABLE"
5. Esperar confirmación

### Paso 2.3: Habilitar My Business API (v4)

1. Volver a [APIs & Services > Library](https://console.cloud.google.com/apis/library)
2. Buscar: `My Business API`
3. Click en resultado (versión v4, no v4.1)
4. Click en "ENABLE"
5. Esperar confirmación

Verificación: Ir a [APIs & Services > Enabled APIs](https://console.cloud.google.com/apis/dashboard) y confirmar que aparecen las 3 APIs.

## 3. Configurar Pantalla de Consentimiento OAuth

La pantalla de consentimiento es lo que ven los usuarios cuando autorizan la app.

### Paso 3.1: Ir a Pantalla de Consentimiento

1. Ir a [APIs & Services > OAuth consent screen](https://console.cloud.google.com/apis/consent)
2. Seleccionar tipo de usuario: **External** (no Internal a menos que sea empresa)
3. Click en "CREATE"

### Paso 3.2: Llenar Información de la App

En sección "App information":

- **App name**: `Reviews Integration` (o nombre de tu app)
- **User support email**: tu email o email de soporte
- **Authorized domains** (opcional): `tu-dominio.com`

En sección "Developer contact information":

- **Email addresses**: tu email de desarrollador

Click en "SAVE AND CONTINUE"

### Paso 3.3: Definir Scopes

En sección "Scopes":

1. Click en "ADD OR REMOVE SCOPES"
2. Buscar y seleccionar:
   - `https://www.googleapis.com/auth/business.manage` (requerido)
3. Click en "UPDATE"
4. Click en "SAVE AND CONTINUE"

### Paso 3.4: Agregar Usuarios de Prueba

En sección "Test users":

1. Click en "ADD USERS"
2. Agregar tu email y otros emails de prueba
3. Click en "ADD"
4. Click en "SAVE AND CONTINUE"

## 4. Crear Credenciales OAuth 2.0

### Paso 4.1: Ir a Credenciales

1. Ir a [APIs & Services > Credentials](https://console.cloud.google.com/apis/credentials)
2. Click en "CREATE CREDENTIALS" > "OAuth 2.0 Client ID"
3. Si aparece advertencia sobre pantalla de consentimiento, click en "Configure Consent Screen" y volver a paso 3

### Paso 4.2: Seleccionar Tipo de Aplicación

1. En desplegable "Application type", seleccionar: **Web application**
2. Ingresar nombre: `Reviews Integration Web` (o similar)
3. Click en "CREATE"

### Paso 4.3: Configurar Redirect URIs

Antes de crear, en sección "Authorized redirect URIs":

1. Click en "ADD URI"
2. Agregar todas las URLs de callback:

   ```
   https://tu-dominio.com/settings/reviews/google/callback
   https://tu-dominio-staging.com/settings/reviews/google/callback
   http://localhost:8000/settings/reviews/google/callback
   ```

3. Click en "CREATE"

### Paso 4.4: Copiar Credenciales

Se abrirá un modal con tus credenciales:

```
Client ID: xxxxxxxxxxxxxxxxx.apps.googleusercontent.com
Client Secret: xxxxxxxxxxxxxxxxxxxxxxx
```

**Importante**: No perder ni compartir el Client Secret.

Copiar ambos valores a `.env`:

```env
GOOGLE_CLIENT_ID=xxxxxxxxxxxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxxxxxxxxxxxxxxxxxxxxxx
```

O guardar en lugar seguro para configuraciónlater.

Click en "OK" para cerrar modal.

## 5. Configurar Dominios Autorizados

### Paso 5.1: Ir a OAuth Client Settings

1. En [APIs & Services > Credentials](https://console.cloud.google.com/apis/credentials)
2. Buscar el OAuth Client ID que acabamos de crear
3. Click en el lápiz (edit)

### Paso 5.2: Agregar Dominios

En sección "Authorized JavaScript origins":

1. Click en "ADD URI"
2. Agregar dominio raíz:

   ```
   https://tu-dominio.com
   https://tu-dominio-staging.com
   http://localhost:8000
   http://127.0.0.1
   ```

3. Click en "SAVE"

En sección "Authorized redirect URIs" (si no está completo):

1. Verificar que aparece: `/settings/reviews/google/callback`
2. Si no, agregar manualmente

## 6. Configurar Aplicación Laravel

### Paso 6.1: Variables de Entorno

En archivo `.env` del proyecto:

```env
# Google OAuth Credentials
GOOGLE_CLIENT_ID=xxxxxxxxxxxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxxxxxxxxxxxxxxxxxxxxxx

# URLs
APP_URL=https://tu-dominio.com
APP_ENV=production                    # o development

# Configuración Reviews
REVIEWS_SYNC_INTERVAL=15              # Minutos entre sincronizaciones
REVIEWS_AUTO_PUBLISH=false            # No auto-publicar respuestas
REVIEWS_DEFAULT_VISIBLE=true          # Reseñas visibles por defecto
```

### Paso 6.2: Verificar Configuración

```bash
php artisan tinker
> config('reviews.google.client_id')
> config('reviews.google.client_secret')
> config('reviews.google.redirect_uri')
```

Debe devolver algo como:

```
"xxxxxxxxxxxxxxxxx.apps.googleusercontent.com"
"xxxxxxxxxxxxxxxxxxxxxxx"
"https://tu-dominio.com/settings/reviews/google/callback"
```

## 7. Prueba de Conexión

### Paso 7.1: Acceder al Panel

1. Ir a `https://tu-dominio.com/settings/reviews/connections`
2. Hacer click en botón "Connect Google Account"

### Paso 7.2: Flujo OAuth

1. Redirige a Google para autorización
2. Seleccionar cuenta Google
3. Acepta permisos solicitados
4. Redirige de vuelta a la app

### Paso 7.3: Verificar Conexión

Si todo va bien:

1. Aparece mensaje de éxito
2. Nueva conexión aparece en la lista
3. Status: "Active"

Si hay error, ver sección "Troubleshooting" abajo.

## 8. Seleccionar Ubicaciones para Sincronizar

Después de conectar la cuenta:

1. Ir a `/settings/reviews/locations`
2. Hacer click en "Sync Locations" para descargar ubicaciones de Google Business Profile
3. Seleccionar las ubicaciones que quieres sincronizar
4. Cambiar estado a "Active" para cada ubicación
5. Guardar

La sincronización automática empezará en los próximos 15 minutos.

## Troubleshooting

### Error: "Invalid Client ID"

**Causa**: Cliente ID mal copiado o mal configurado

**Solución**:

1. Verificar que `GOOGLE_CLIENT_ID` en `.env` es correcto
2. Copiar exactamente desde Google Cloud Console (incluyendo .apps.googleusercontent.com)
3. Limpiar cache: `php artisan config:clear`

### Error: "Redirect URI mismatch"

**Causa**: URL de callback no coincide con la configurada en Google

**Solución**:

1. En `config/reviews/google.php`, verificar que `redirect_uri` es correcto
2. En Google Cloud Console, agregar la URL exacta a "Authorized redirect URIs"
3. Esperar 5 minutos para que Google procese cambios

**Nota**: URLs deben ser exactas, incluyendo protocolo (http vs https)

### Error: "Invalid OAuth state"

**Causa**: Token de sesión expirado durante flujo OAuth

**Solución**:

```bash
# Limpiar sesión
php artisan cache:clear

# Verificar que Redis está corriendo (si usa Redis para sesiones)
redis-cli ping
```

O cambiar driver de sesión a database:

```env
SESSION_DRIVER=database
```

### Error: "Access blocked by administrator"

**Causa**: Empresa tiene política de acceso a apps OAuth

**Solución**:

Contactar con administrador de Google Workspace para:

1. Aprobar la app OAuth
2. Agregar dominio a lista de apps confiables
3. Permitir acceso a APIs

### Error: "User not authorized"

**Causa**: Usuario no es dueño/manager de la ubicación en Google Business Profile

**Solución**:

1. Ir a [Google Business Profile](https://www.google.com/business)
2. Asegurar que el usuario está agregado a la ubicación
3. Dar permisos de "Manager" o superior
4. Desconectar y reconectar OAuth

### Error: "The user is not authenticated" al conectar

**Causa**: No estás logged in a la app

**Solución**:

1. Hacer login a la aplicación antes de conectar Google
2. Verificar sesión: `php artisan tinker > auth()->user()`

### Las ubicaciones no aparecen

**Causa**: Ubicaciones no están creadas/verificadas en Google Business Profile

**Solución**:

1. Ir a [Google Business Profile](https://www.google.com/business)
2. Crear nueva ubicación si es necesario
3. Completar proceso de verificación (SMS, correo, etc)
4. Esperar a que Google las sincronice (hasta 48 horas)
5. Click en "Sync Locations" nuevamente

### Token expira muy rápido

**Causa**: Refresh token no fue guardado o configuración incorrecta

**Solución**:

Verificar en base de datos:

```bash
php artisan tinker
> ReviewGoogleConnection::first()
```

Debe tener `refresh_token` (no null).

Si no tiene, reconectar cuenta:

1. Revocar token en Google Cloud Console
2. Desconectar en la app
3. Reconectar

## Renovación de Credenciales

### Cada Cuánto Renovar

- **Access Token**: Automáticamente cada 1 hora (manejado por GoogleAuthService)
- **Refresh Token**: Se mantiene válido indefinidamente (no expira)
- **Credenciales de Google Cloud**: Solo si cambias Client Secret voluntariamente

### Renovar Manualmente

Si una conexión está expirada:

```bash
php artisan reviews:cleanup-expired
```

O vía Tinker:

```bash
php artisan tinker
> \Modules\Reviews\Models\ReviewGoogleConnection::find(1)->refreshTokenIfNeeded()
```

## Seguridad

### Mejores Prácticas

1. **Nunca committear credenciales**: Usar `.env` y `.env.example`
2. **Rotación de secrets**: Cambiar Secret cada 6 meses en producción
3. **Encriptación**: Los tokens se guardan encriptados en BD (automático)
4. **HTTPS obligatorio**: Usar HTTPS en producción
5. **Rate Limiting**: Configurar límites de API para evitar abuse
6. **Auditoría**: Revisar activity logs regularmente

### Revocar Acceso

Para desconectar una cuenta:

1. En `/settings/reviews/connections`, click en "Revoke"
2. O vía Tinker:

   ```bash
   php artisan tinker
   > ReviewGoogleConnection::find(1)->markAsRevoked()
   ```

3. El token se marca como revocado pero se conserva el historial
4. Los datos (reseñas, etc) no se eliminan automáticamente

## Referencias

- [Google Business Profile API Docs](https://developers.google.com/my-business/content/overview)
- [OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Google Cloud Console](https://console.cloud.google.com)
- [Google API Explorer](https://developers.google.com/apis-explorer)
