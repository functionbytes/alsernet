# Guía de desarrollo seguro — webadmin (Laravel + módulos)

**Obligatoria para cualquier módulo, ruta o cambio nuevo.** Nace del incidente del 26-sep-2026
y de la auditoría completa del 29-sep-2026 (48 módulos, 2.304 rutas).

El incidente se resume así:
1. Una ruta pública de subida guardaba el archivo **con el nombre que mandaba el cliente**.
2. Lo guardaba en el disco **`public`**, que Apache sirve directamente.
3. El atacante subió un `.htaccess` y un `.gif` con PHP.
4. Así ejecutó comandos en el servidor.
5. Con eso leyó el `.env` y se llevó credenciales de pagos y datos de la tienda.

Casi todo lo que sigue sale de fallos reales encontrados en este proyecto.

> Regla general: **lo que no se valida en el servidor no está validado**. El formulario, el
> JavaScript, "solo lo usa la tienda" o "nadie conoce esta URL" no son controles de seguridad.

---

## 1. Las 10 reglas que no se negocian

1. **Nunca uses el nombre ni la extensión que manda el cliente para guardar un archivo.** El
   nombre lo genera el servidor.
2. **Nada que llegue de fuera va al disco `public`** salvo que deba ser público para todo
   Internet. Lo privado se sirve por un controlador que comprueba permisos.
3. **Toda ruta que modifica datos o devuelve datos personales exige autenticación y
   autorización** (`can:` o `authorize()`). Si es servidor a servidor, firma HMAC.
4. **Un identificador no es un secreto.** Un `uid`, un `order_id` o un `id` no autorizan nada
   por sí solos.
5. **Rutas de ficheros del sistema: siempre `realpath()` y comprobar que el resultado queda
   dentro del directorio permitido.** Nunca `strpos`/`str_starts_with` sobre lo que llega.
6. **Toda petición HTTP saliente hacia una URL que no escribiste tú pasa por
   `OutboundUrlGuard`**, y cada redirección se vuelve a validar.
7. **No se escribe el `.env` ni ficheros de código desde la web.** La configuración editable
   vive en la tabla `settings`, y los secretos cifrados.
8. **Los datos de usuario se escapan siempre al pintarlos:** `{{ }}` en Blade, y en JavaScript
   un escape que incluya comillas o construir el DOM con `.text()`/`.attr()`.
9. **Sin `exec`, `shell_exec`, `system`, `eval` ni `unserialize` con datos externos.** Artisan
   solo con una lista cerrada de comandos.
10. **Producción:** `APP_DEBUG=false`, `composer install --no-dev`, sin rutas utilitarias
    públicas (`/clear`, `/phpinfo`, `/test`…), sin contraseñas por defecto y con 2FA para
    administradores.

---

## 2. Subida de archivos

### 2.1 Por qué `mimes:` no basta
`mimes:pdf` comprueba el tipo **deducido del contenido** (`finfo`), no el nombre. Un fichero
llamado `.htaccess` que contenga una línea `# %PDF-1.4` se detecta como PDF y **pasa la
validación**. Lo mismo ocurre con un GIF con PHP dentro llamado `shell.php`.
`ValidMimeMagicBytes` tampoco lo evita. **La protección real es que el nombre en disco lo
genere el servidor.**

### 2.2 Patrón obligatorio (controlador o servicio normal)
```php
use Illuminate\Validation\Rule;
use Modules\Core\Rules\ValidMimeMagicBytes;

$request->validate([
    'file' => [
        'required', 'file', 'max:10240',
        'extensions:pdf,jpg,jpeg,png,webp',           // extensión del NOMBRE (Laravel ≥ 10.36)
        'mimes:pdf,jpg,jpeg,png,webp',                // tipo deducido del CONTENIDO
        new ValidMimeMagicBytes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp']),
    ],
]);

$file = $request->file('file');

// store() usa hashName(): 40 caracteres aleatorios + extensión según el contenido.
$path = $file->store("modulo/{$registro->id}", 'local');   // disco PRIVADO

$registro->adjuntos()->create([
    'path'          => $path,
    'disk'          => 'local',
    'original_name' => Str::limit($file->getClientOriginalName(), 200), // solo para MOSTRAR
    'mime'          => $file->getMimeType(),
    'size'          => $file->getSize(),
]);
```

**Prohibido:**
```php
$file->storeAs($dir, $file->getClientOriginalName(), 'public');           // ❌
$file->storeAs($dir, $nombre.'.'.$file->getClientOriginalExtension());    // ❌
$file->move(public_path('uploads'), $file->getClientOriginalName());      // ❌
Storage::putFileAs($dir, $file, $request->input('filename'));             // ❌
```

### 2.3 Spatie Media Library (Document, Media…)
El sanitizador por defecto de Media Library **conserva** nombres como `.htaccess`, `.user.ini`,
`x.html` o `x.svg`. Hay que fijar siempre el nombre y el disco:
```php
// En el modelo:
public function registerMediaCollections(): void
{
    $this->addMediaCollection('documents')
        ->useDisk('documents_private')     // disco local, root fuera de public/
        ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp']);
}

// Al añadir:
$modelo->addMedia($file)
    ->usingFileName(Str::uuid().'.'.$file->guessExtension())
    ->withCustomProperties(['original_name' => $file->getClientOriginalName()])
    ->toMediaCollection('documents');
```

### 2.4 Extensiones que nunca se aceptan
`php php3 php4 php5 php7 php8 phtml pht phar phps inc htaccess ini user.ini cgi pl py sh
shtml html htm xhtml xml svg svgz js mjs` y cualquier nombre que **empiece por punto**. Si una
lista de extensiones es configurable desde el panel, esta lista negra va fija en el código
(`Rule::notIn([...])`).

- **Imágenes:** si se van a mostrar, re-codifícalas (GD/Imagick) para eliminar metadatos y
  contenido escondido.
- **SVG:** no se acepta de usuarios, porque ejecuta JavaScript.

### 2.5 Descargar archivos de una URL ("importar desde URL", adjuntos de Meta…)
- La URL pasa por `OutboundUrlGuard` (ver el apartado 7).
- El nombre y la extensión se derivan del **MIME real** con un mapa propio. Lo desconocido va
  a `.bin`. Nunca uses el nombre de la URL ni un `filename` de la petición.

### 2.6 Servir archivos privados
```php
public function download(Adjunto $adjunto)
{
    $this->authorize('view', $adjunto);     // policy: comprueba el dueño
    return Storage::disk($adjunto->disk)->download(
        $adjunto->path,
        $adjunto->original_name,
        ['X-Content-Type-Options' => 'nosniff']   // download() ya pone Content-Disposition: attachment
    );
}
```
Si hace falta un enlace para un tercero (correo, integración), usa
`URL::temporarySignedRoute(...)` con caducidad corta. Nunca una URL `/storage/...`.

### 2.7 Subidas por trozos, ZIP y antivirus
- **Subidas por trozos:** el archivo reconstruido pasa **la misma validación** que una subida
  normal.
- **ZIP:** no se descomprimen desde la web. Si es imprescindible, en un directorio aislado, con
  control de `..`, de rutas absolutas y de tamaño total (zip bomb), y validando cada archivo
  como si fuera una subida.
- **Antivirus:** `AttachmentSecurityService` (Helpdesk) usa ClamAV. Si se activa sin ClamAV
  instalado, falla en cerrado y bloquea todas las subidas. ClamAV **no** detecta webshells
  hechos a mano: es un complemento, no sustituye a las reglas anteriores.

---

## 3. Discos y almacenamiento

| Disco | Se sirve por URL | Úsalo para |
|---|---|---|
| `public` (`storage/app/public` → `/storage`) | **Sí, sin login** | Solo contenido pensado para ser público: logos, imágenes de productos… |
| `media` (`public/media`) | **Sí** | Lo mismo que `public`. |
| `local` / discos privados (`storage/app/...`) | No | Documentos de identidad, adjuntos de clientes, PDFs con datos personales, exportaciones, backups. |

- **Nombres adivinables:** un archivo en `public` con nombre como `<nºpedido>-tarjeta.pdf` o
  `AAAA/MM/DD/<nombre>` es una fuga de datos, aunque tu controlador compruebe permisos.
- **Adjuntos de Helpdesk:** `HELPDESK_ATTACHMENTS_DISK` debe ser `local`.
- **Servidor web:** `storage/app/public` y `storage/app/media` tienen en el vhost
  `AllowOverride None`, sin ejecución de PHP/CGI y con `X-Content-Type-Options: nosniff`. El
  `DocumentRoot` es `/home2/...`, que es un symlink de `/home`. Por eso los bloques de Apache
  deben usar `<DirectoryMatch "^/home2?/webadmin/web/...">`. Un `<Directory "/home/...">` **no
  se aplica**.

---

## 4. Rutas públicas

Antes de crear una ruta sin `auth`, responde por escrito en el PR:

1. **¿Por qué tiene que ser pública?** Si solo la llama otro servidor nuestro (tienda, ERP),
   **no es pública**: lleva firma HMAC (apartado 6) y, si se puede, lista de IPs.
2. **¿Qué pasa si alguien la llama con un `id` o un `email` ajenos?** Si devuelve o modifica
   algo de otra persona, falta autorización.
3. **¿Tiene `throttle`?** Los limitadores no deben vivir en un store que se vacíe con
   `cache:clear`. Desde el 29-sep-2026 `RateLimiter` usa el store `limiter` (Redis BD 3,
   `config/cache.php`), que `cache:clear` no toca: usa `throttle:` o `RateLimiter::`, no
   `Cache::` a mano.
4. **¿Devuelve identificadores, URLs de archivos o datos personales que no necesita?** Quítalos.
   Una respuesta pública solo lleva lo imprescindible.
5. **¿Confía en un email, un teléfono o un ID que manda el cliente para asociarlo a una ficha
   existente?** Un email no verificado **nunca** da acceso a la ficha, las conversaciones o los
   pedidos de ese cliente. Crea un registro nuevo marcado como "no verificado".
6. **Simuladores, demos y herramientas de prueba:** nunca activos con `APP_ENV=production`,
   aunque un `.env` diga lo contrario:
   ```php
   'simulator_public_enabled' => env('APP_ENV') !== 'production'
       && (bool) env('HELPDESK_SIMULATOR_PUBLIC', false),
   ```

---

## 5. Autenticación y autorización

- **Doble control:** cada grupo de rutas lleva `can:<permiso>` **y** cada acción de
  controlador llama a `$this->authorize()` o usa una FormRequest con `authorize()`.
- **Middleware que no comprueba nada:** no confíes en uno que "suena" a seguridad sin leerlo.
  `settings` (`CheckSettings`) **no hacía nada**; mientras lo arreglamos, cada controlador debe
  autorizar por su cuenta.
- **Policies con dueño:** `view`/`update`/`delete` comprueban que el recurso pertenece al usuario
  o a su bandeja o equipo. Usa `Route::scopeBindings()` en rutas anidadas
  (`/forms/{form}/submissions/{submission}`).
- **Jerarquía de roles:** nadie puede asignar, editar, impersonar ni cambiar la contraseña de
  un usuario con un rol **superior** al suyo. Los roles protegidos (`super-admin`…) solo los
  gestiona un `super-admin`.
- **Impersonación:**
  - duración máxima;
  - `session()->regenerate()` al empezar y al terminar;
  - `DenyWhenImpersonating` en tokens API, cambio de email, contraseña y 2FA.
- **Login:**
  - todas las vías (web **y** API) pasan por el mismo servicio, con bloqueo de cuenta y
    contador de fallos;
  - el reto 2FA es de un solo uso;
  - las respuestas son idénticas exista o no el usuario;
  - la recuperación de contraseña usa `Password::sendResetLink()` (broker nativo).
- **Contraseñas:**
  - los seeders **nunca** llevan contraseñas fijas (`Hash::make(Str::random(32))` +
    `must_change_password`) y los de usuarios no se ejecutan en producción;
  - 2FA obligatorio para `super-admin`, `super-settings` y `helpdesk-admin`;
  - cast `encrypted` para `two_factor_secret` y `encrypted:array` para los códigos de
    recuperación, que además van en `$hidden`.
- **CSRF:**
  - no añadas prefijos amplios a `VerifyCsrfToken::$except` (`api/*` dejaba 54 rutas de sesión
    sin CSRF). Excluye solo URIs exactas de webhooks;
  - `SESSION_SAME_SITE=lax`.

---

## 6. Webhooks y llamadas servidor a servidor

Patrón usado en el proyecto (HelpdeskPrestashop, Reviews, Questions, Document): HMAC sobre
`timestamp:body`, ventana de 5 minutos, nonce contra reenvíos y cierre si falta el secreto.
```php
public function handle(Request $request, Closure $next)
{
    $secret = (string) config('modulo.webhook_secret');
    abort_if($secret === '', 503);                                   // sin secreto: cerrado

    $ts  = (string) $request->header('X-Timestamp');
    $sig = (string) $request->header('X-Signature');
    abort_unless(ctype_digit($ts) && abs(time() - (int) $ts) <= 300, 401);

    $expected = hash_hmac('sha256', $ts.':'.$request->getContent(), $secret);
    abort_unless(hash_equals($expected, $sig), 401);
    abort_unless(Cache::add('whk:'.$sig, 1, 600), 409);              // anti-replay

    return $next($request);
}
```
- Proveedores con su propio esquema (Meta `X-Hub-Signature-256`, Mailgun, SES/SNS): verifica
  **también** el `timestamp` y, en SNS, que el `TopicArn` sea el nuestro.
- Nunca aceptes el token del webhook por query string: acaba en los logs de Apache.
- Una API "interna" (como `/api/erp/*`) también se autentica con token por consumidor. La lista
  de IPs de Apache es un control adicional, no el único. Si la opción de autenticación falta en
  la configuración, se trata como **activada** (fail-closed).

---

## 7. Peticiones HTTP salientes (SSRF)

Cualquier URL que venga de la petición, de un formulario del panel, de una fuente de proveedor
o de un mensaje:
```php
use Modules\Helpdesk\Support\OutboundUrlGuard;

abort_unless(OutboundUrlGuard::isSafe($url), 422);   // solo http(s) y solo IPs públicas

$response = Http::timeout(10)
    ->withOptions([
        'allow_redirects' => [
            'max' => 3,
            'on_redirect' => function ($req, $res, $uri) {
                if (! OutboundUrlGuard::isSafe((string) $uri)) {
                    throw new \RuntimeException('Redirección bloqueada');
                }
            },
        ],
    ])
    ->get($url);
```
- Para cerrar también el DNS rebinding, fija la IP resuelta (`CURLOPT_RESOLVE`), como el trait
  `GuardsAgainstSsrf` de ChatFlow.
- Valida la URL **al guardarla y al usarla**, no solo en el botón "probar conexión".
- Si la URL tiene un destino conocido (ERP, proveedores), usa una **lista blanca de hosts**.
- No devuelvas al usuario el cuerpo de la respuesta ni el mensaje de la excepción: basta con
  el código HTTP.

---

## 8. Ficheros del sistema y path traversal

```php
$base = realpath(config_path('supervisor'));         // directorio permitido
$real = realpath($base.DIRECTORY_SEPARATOR.basename($request->input('file')));

abort_unless(
    $real !== false
    && str_starts_with($real, $base.DIRECTORY_SEPARATOR)
    && preg_match('/\.conf$/', $real),
    404
);
```
- Mejor todavía: el usuario elige de una **lista** de ficheros existentes y envía un índice o un
  `basename`, nunca una ruta.
- `Storage::disk()` (Flysystem) ya bloquea `..` fuera de la raíz. Úsalo en vez de
  `file_get_contents`/`response()->file()` con rutas construidas.
- Nunca `require`/`include` con variables que vengan de la petición.

---

## 9. Comandos, código y configuración en tiempo de ejecución

- **Desde la web:** sin `exec`, `shell_exec`, `system`, `passthru`, `popen`, `proc_open` ni
  `Process::fromShellCommandline`. Si un panel necesita una acción del sistema, lanza un
  **job** que la ejecute por CLI.
- **`Process`:** si es imprescindible, siempre con **array** de argumentos
  (`new Process(['clamscan', '--no-summary', $path])`) y valores validados.
- **`Artisan::call`:** solo con una lista cerrada de comandos (`in:`) y sin opciones
  destructivas (`--fresh`, `--force`) disparables por usuarios sin rol de administración.
- **Prohibidos con datos externos:** `eval`, `assert`, `unserialize`,
  `Blade::render`/`compileString`.
- **Instalar módulos o código (ZIP) desde el panel:** prohibido en producción.
- **`.env` y ficheros de código:** no se escriben desde la web. Tampoco generes ficheros de
  configuración (supervisor, cron) con valores sin validar: se pueden inyectar líneas.
- **`config:cache`/`cache:clear`:** en el deploy, nunca desde una ruta.

---

## 10. Base de datos

- Siempre con bindings: `whereRaw('x = ?', [$v])`, `DB::select($sql, $bindings)`.
- Nunca concatenes input en `DB::raw`, `whereRaw`, `orderByRaw`, `selectRaw` o
  `DB::statement`.
- Columnas y direcciones de orden con lista blanca:
  ```php
  $sort = match ($request->input('sort')) { 'fecha' => 'created_at', 'nombre' => 'name', default => 'id' };
  $dir  = $request->input('dir') === 'asc' ? 'asc' : 'desc';
  ```
- `$fillable` explícito. Nada de `$guarded = []` combinado con `$request->all()`: usa
  `$request->validated()`.
- **Mínimo privilegio en conexiones externas:** el usuario de la BD de la tienda que usa
  webadmin solo debe leer las tablas que necesita, nunca `configuration`, datos de pago ni
  claves del webservice.

---

## 11. XSS

- **Blade:** `{{ $valor }}`. `{!! !!}` solo con HTML propio o saneado (`clean_html()`,
  `HtmlSanitizer::clean()`), y también **al guardar** el contenido generado por IA o por
  editores.
- **JavaScript:** `$('<div>').text(s).html()` y `textContent → innerHTML` **no escapan
  comillas**. No sirven dentro de atributos. Usa un único helper común:
  ```js
  function escapeAttr(s) {
      return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
          .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
  ```
  O mejor, construye los nodos con `$('<img>').attr('alt', nombre)` / `.text(cuerpo)`.
- **Datos que escribe un cliente:** el asunto, el nombre de perfil de WhatsApp o el nombre de un
  adjunto llegan al panel de los agentes. Trátalos como hostiles. Aplica `strip_tags` a los
  asuntos al crearlos.
- **CSS y JS configurables:** CSS personalizable y hooks de JS o HTML desde settings, solo para
  `super-admin`. Filtra `</style` en el CSS.
- **Objetivo:** CSP con `script-src 'self'`. Hoy hay una CSP en modo informe
  (`Content-Security-Policy-Report-Only`, informes en `storage/logs/csp-*.log`); cómo pasarla a
  bloqueante: `docs/seguridad/CABECERAS_Y_VIGILANCIA.md`. Un recurso externo nuevo (CDN,
  fuente, iframe, WebSocket) se añade a `config/security.php` → `csp.directives`.

---

## 12. Secretos y configuración

- **Dónde viven:** los secretos, en el `.env` o en `settings` **cifrados** con
  `Setting::setEncrypted()`/`getDecrypted()` o con cast `encrypted`/`encrypted:array` en el
  modelo.
- **Formularios:** un campo de contraseña **nunca** lleva `value="{{ ... }}"`. Déjalo vacío con
  `placeholder="(sin cambios)"` y actualízalo solo si viene relleno.
- **Nada hardcodeado:** ni credenciales ni contraseñas en código, seeders, tests o
  documentación.
- **Logs:** no registres contraseñas, tokens, cabeceras `Authorization`/`Cookie` ni comandos
  con credenciales (`mysqldump -p…`: usa `MYSQL_PWD` o `--defaults-extra-file` con permisos
  0600).
- **No expongas `laravel.log`** desde módulos. Cada módulo tiene su propio canal si necesita
  enseñar logs.
- **Tras cualquier sospecha de fuga:** rota `APP_KEY` (`APP_PREVIOUS_KEYS` mientras se
  re-cifra) y **todas** las credenciales externas.

---

## 13. Tiempo real (Reverb / Echo)

- Datos de usuarios, pedidos o documentos **solo por `PrivateChannel`/`PresenceChannel`**,
  autorizados en `routes/channels.php`.
- Un `Channel` público no pasa por `/broadcasting/auth`: cualquiera se puede suscribir.
- `allowed_origins` de Reverb limitado a nuestros dominios.

---

## 14. Permisos del servidor y despliegue

- **Propietario y permisos:** `maxi:www-data`. Código en `755`/`644`. Solo `storage/` y
  `bootstrap/cache` escribibles por `www-data` (`775`). **Nunca 777.** El `.env` en
  `640 maxi:www-data`.
- **Grupos de `www-data`:** no pertenece a grupos de desarrolladores. Si lo hace, puede escribir
  todo el código.
- **Deploy:** `composer install --no-dev --optimize-autoloader`, `php artisan config:cache
  route:cache view:cache`, `APP_DEBUG=false`, `LOG_LEVEL=warning`.
- **PHP-FPM:** `security.limit_extensions = .php`. Valora `disable_functions` (exec,
  shell_exec, system, passthru, popen, proc_open) sabiendo qué paneles lo usan.
- **Backups:** fuera de `public/`, con permisos `600`, y sin rutas web que los listen o
  descarguen sin permiso específico.
- **Código muerto:** bórralo. Un método sin uso que guarda archivos en `public/` o monta un
  `php -r` acaba reutilizándose.

---

## 15. Checklist para cada PR (cópialo en la descripción)

```
Seguridad
[ ] Rutas nuevas: ¿públicas? justificación / HMAC / throttle / no devuelven datos de más
[ ] Todas las acciones con can:/authorize() y policy de dueño (no basta con 'auth')
[ ] Subidas: extensions: + mimes: + nombre generado por el servidor + disco privado
[ ] Ningún getClientOriginalName()/Extension() en rutas de disco
[ ] Descargas de ficheros privados vía controlador con authorize (no /storage/...)
[ ] Rutas de fichero con realpath() + comprobación de directorio base
[ ] HTTP saliente a URLs configurables: OutboundUrlGuard + revalidar redirecciones
[ ] SQL solo con bindings; orderBy con lista blanca; sin $guarded = [] + all()
[ ] Blade {{ }}; {!! !!} solo con HTML saneado; JS escapa comillas en atributos
[ ] Sin exec/eval/unserialize/Artisan libre; sin escritura de .env ni de código
[ ] Secretos cifrados; ningún value= en campos password; nada en logs
[ ] Broadcasting: datos personales solo en canales privados
[ ] Seeders sin contraseñas fijas; nada activo solo para "pruebas" en producción
[ ] php artisan security:audit sin novedades (o justificadas aquí)
```

---

## 16. Comprobaciones automáticas (ejecutar antes de cada merge)

**Comando:** `php artisan security:audit` ejecuta las búsquedas 1-6 y la comparación de rutas
públicas (7) de este apartado y sale con código 1 si hay resultados nuevos respecto a
`docs/seguridad/auditoria_linea_base.json` o rutas públicas que no están en
`docs/seguridad/rutas_publicas_aprobadas.txt`. `--details` lista todo; tras justificar las
novedades en el PR, `--update-baseline` / `--update-routes` actualizan los dos ficheros.
Además, `php artisan security:watch` corre cada 5 min en producción (ficheros ejecutables en
subidas, cambios de código, denegaciones ERP, logins fallidos) y avisa a los `super-admin`:
ver `docs/seguridad/CABECERAS_Y_VIGILANCIA.md`.

Las búsquedas equivalentes a mano:

Son búsquedas de **revisión**, no de aprobado/suspenso: listan los puntos que un humano debe
mirar. Cada resultado **nuevo** que añada un PR se justifica en su descripción. Son búsquedas
por línea, así que un `->usingFileName(...)` en la línea siguiente no se ve; léelo en el
fichero antes de dar un resultado por malo. Excluimos `tests/` y el código de terceros de
`modules/Prestashop/integrations/`.
```bash
X='/tests/|Prestashop/integrations|/vendor/|/node_modules/'

# 1) Nombre o extensión del cliente (cada uso: ¿acaba en una ruta de disco?)
grep -rnE 'getClientOriginal(Name|Extension)\(' modules app --include=*.php | grep -vE "$X"
# 2) Media Library sin nombre fijado (por defecto CONSERVA el nombre del cliente)
grep -rnE -A3 'addMedia(FromRequest|FromUrl)?\(' modules app --include=*.php | grep -vE "$X" | grep -B3 'toMediaCollection' | grep -v usingFileName
# 3) Escrituras en el disco public (¿debería ser privado?)
grep -rnE "(store|storeAs|putFile|putFileAs)\([^)]*['\"]public['\"]|disk\(['\"]public['\"]\)->put" modules app --include=*.php | grep -vE "$X"
# 4) Ejecución de comandos / código (fuera de comandos de consola)
grep -rnE '\b(shell_exec|exec|system|passthru|popen|proc_open)\s*\(|fromShellCommandline|\beval\s*\(|\bunserialize\s*\(' modules app --include=*.php | grep -vE "$X|->exec\(|Console/Commands"
# 5) SQL con variables de la petición
grep -rnE '(whereRaw|orderByRaw|selectRaw|havingRaw|DB::raw|DB::statement)\([^)]*\$(request|input|_GET|_POST)' modules app --include=*.php
# 6) Validación de rutas con strpos (debe ser realpath + base)
grep -rnE 'strpos\(\$[a-zA-Z]*[Pp]ath' modules app --include=*.php | grep -vE "$X"
# 7) Rutas sin autenticación: comparar con la lista aprobada del último release
php artisan route:list --json | jq -r '.[] | select((.middleware|tostring|test("auth|can:|signed|sanctum|Authorize|ValidateSignature";"i"))|not) | .method+" "+.uri' | sort > /tmp/rutas_publicas.txt
diff <(grep -v '^#' docs/seguridad/rutas_publicas_aprobadas.txt) /tmp/rutas_publicas.txt
```
La lista del comando 7 se basa en nombres de middleware. Un middleware que **se llame** como
uno de autenticación pero no compruebe nada (pasó con `ApiAuth` de la API ERP y con
`settings`) la engaña. Por eso cada middleware nuevo de seguridad lleva un test que demuestre
que **rechaza** una petición sin credenciales.
Referencia (29-sep-2026, antes de aplicar los arreglos): 1 → 36 usos, 3 → 9 escrituras en
`public`, 4 → 15 puntos, 5 → 0, 6 → 18. El número no debe subir sin justificación.
La línea base que usa `security:audit` se tomó al final del 29-sep-2026, con los arreglos de
ese día aplicados (ver `auditoria_linea_base.json`). La búsqueda 2 a mano (`grep -A3 … | grep
-B3`) da falsos positivos con las líneas de contexto; el comando mira la ventana de 4 líneas.

Tests recomendados (Pest) en cada módulo que acepte archivos:
- subir `.htaccess` con contenido `# %PDF-1.4` → 422, o el nombre final no es `.htaccess`;
- subir `x.php` con cabecera `GIF89a` → rechazado;
- subir `x.html` / `x.svg` → rechazado;
- el archivo guardado no está en el disco `public`;
- la ruta de descarga devuelve 403 para otro usuario.

---

*Referencias internas:* el informe completo de la auditoría (fallos abiertos, no se sube al
repositorio) está en `storage/AUDITORIA_SEGURIDAD_2026-09-29.md` del servidor, con el detalle
por grupo en `storage/auditoria_2026-09-29/`.
