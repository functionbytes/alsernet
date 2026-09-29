# Cabeceras de seguridad, CSP, limitador y vigilancia automática

*Implantado el 29-sep-2026. Configuración centralizada en `config/security.php`.*

---

## 1. Cabeceras HTTP

Middleware **global** `Modules\Core\Http\Middleware\SecurityHeaders` (primero de la lista en
`bootstrap/app.php`, así envuelve web, api, redirecciones, descargas y páginas de error).

| Cabecera | Valor | Quién la pone |
|---|---|---|
| `X-Content-Type-Options` | `nosniff` | app |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | app |
| `Permissions-Policy` | cámara/micro/captura de pantalla solo `self` (WebRTC del Helpdesk); geolocalización, pago, USB, sensores… desactivados | app |
| `X-Robots-Tag` | `noindex, nofollow, noarchive, nosnippet` en **todas** las respuestas (webadmin no se indexa) | app |
| `Content-Security-Policy-Report-Only` | política completa (apartado 2), solo en respuestas HTML | app |
| `Reporting-Endpoints` | `csp-endpoint="https://…/csp-report"` | app |
| `X-Frame-Options` | `SAMEORIGIN` | **Apache** (`Header always set` en el vhost :443) |
| `Strict-Transport-Security` | `max-age=63072000; includeSubdomains` | **Apache** |

X-Frame-Options y HSTS **no** los pone la app porque Apache ya los fuerza y saldrían duplicados.
Si algún día se sirve la app sin ese Apache: `SECURITY_X_FRAME_OPTIONS=SAMEORIGIN` y
`SECURITY_HSTS=true` (HSTS solo se envía en HTTPS).

Los ficheros estáticos que sirve Apache directamente (`public/build`, `public/vendor`,
`/storage/…`) no pasan por Laravel y solo llevan las cabeceras de Apache.

### Rutas incrustables en iframe

El widget de livechat de www.a-alvarez.com **no usa iframe**: la tienda carga
`/build-helpdesklivechat/widget.js`, que inyecta `/hd/assets/main.js` y `main.css`. Eso no se
ve afectado por X-Frame-Options.

Las rutas pensadas para ir dentro de un iframe en otra web (`security.headers.embeddable_paths`:
`forms/embed/*`, `hd/widget*`, `pages/helpdesk-widget*`) reciben un CSP **bloqueante** con solo
`frame-ancestors 'self' https://a-alvarez.com https://*.a-alvarez.com`
(`SECURITY_FRAME_ANCESTORS`). En los navegadores actuales `frame-ancestors` tiene prioridad sobre
el `X-Frame-Options: SAMEORIGIN` de Apache, así que esas rutas se pueden incrustar desde la
tienda y el resto no. Si se añade otra ruta incrustable, añádela a esa lista.

---

## 2. Content-Security-Policy (modo informe)

Se envía como **`Content-Security-Policy-Report-Only`**: el navegador **no bloquea nada**, solo
informa de lo que bloquearía. Los informes llegan a `POST /csp-report`
(`Modules\Core\Http\Controllers\CspReportController`):

- fuera del grupo `web`: sin sesión, cookies ni CSRF (no hace falta excepción en
  `VerifyCsrfToken`); throttle `csp-report` = 60/min por IP; cuerpo máximo 16 KB;
- acepta el formato `report-uri` (`{"csp-report":{…}}`) y el de Reporting API
  (`[{"type":"csp-violation","body":{…}}]`);
- registra un resumen en el canal `csp` → `storage/logs/csp-AAAA-MM-DD.log` (30 días).

La política (inventario del 29-sep-2026) está en `config/security.php` → `csp.directives`:
`'self'` + jsDelivr, cdnjs, code.jquery.com, js.pusher.com, Google Fonts, fonts.bunny.net,
rsms.me; imágenes y audio de cualquier `https:` (adjuntos de Meta/WhatsApp, emails HTML, OSM,
favicons, QR); `connect-src` con `wss://` y `https://` del host de Reverb (`REVERB_HOST`);
iframes de YouTube; `object-src 'none'`, `base-uri 'self'`, `form-action 'self'`,
`frame-ancestors 'self'`. Incluye `'unsafe-inline'` y `'unsafe-eval'` porque hoy hay cientos
de `<script>` inline, `onclick=` y plugins jQuery que los necesitan.

### Cómo pasar a modo bloqueante

1. Dejar el modo informe **al menos 2 semanas** con uso normal (panel, Helpdesk con
   videollamada, Mailer, Media, Forms, portal, formularios públicos).
2. Revisar los informes:
   ```bash
   cd /home/webadmin/web
   # directivas y orígenes más bloqueados
   grep -h csp-violation storage/logs/csp-*.log \
     | grep -oE '"(violated-directive|effectiveDirective)":"[^"]*"|"(blocked-uri|blockedURL)":"[^"]*"' \
     | sort | uniq -c | sort -rn | head -50
   # páginas afectadas
   grep -h csp-violation storage/logs/csp-*.log | grep -oE '"(document-uri|documentURL)":"[^"?]*' \
     | sort | uniq -c | sort -rn | head -30
   ```
3. Por cada origen legítimo, añadirlo a la directiva correspondiente en `config/security.php`.
   Lo que venga de extensiones del navegador (`chrome-extension://`, `moz-extension://`) se
   ignora. Un origen desconocido en `script-src` en una página del panel es un posible XSS:
   investigarlo antes de permitirlo.
4. Cuando una semana entera no deje informes nuevos legítimos: `SECURITY_CSP_REPORT_ONLY=false`
   en `.env` (o cambiar el valor por defecto en `config/security.php`) y `php artisan
   config:clear`. La cabecera pasa a ser `Content-Security-Policy` y sigue informando a
   `/csp-report`. Probar enseguida el panel y el Helpdesk; si algo se rompe, volver a `true`.
5. Siguiente paso (objetivo de la guía, apartado 11): nonces por petición para quitar
   `'unsafe-inline'`/`'unsafe-eval'` de `script-src`, empezando por los layouts `theme` y `auth`.

Si un controlador pone su propio CSP (p. ej. los adjuntos del Helpdesk con
`default-src 'none'; sandbox`), el middleware no lo sustituye.

---

## 3. Limitador de intentos en Redis propio

- Conexión Redis `limiter` (`config/database.php`) → BD `REDIS_LIMITER_DB`, por defecto **3**
  (en este servidor: 0 = webadminpruebas/comparador, 1 = default/sesiones/colas, 2 = caché).
- Store de caché `limiter` (`config/cache.php`) y `'limiter' => 'limiter'`: `RateLimiter`
  (todos los `throttle:*`, bloqueos de login y 2FA) guarda ahí sus contadores.
- `php artisan cache:clear` hace `FLUSHDB` **solo** de la BD de caché (2): los bloqueos
  sobreviven a despliegues. Para vaciar a propósito el limitador:
  `php artisan cache:clear limiter`.
- Funciona sin tocar el `.env`. Si webadminpruebas despliega este código, debe poner
  `REDIS_LIMITER_DB=4` (u otro libre) para no compartir contadores con producción.

---

## 4. Vigilancia automática: `php artisan security:watch`

Programado cada 5 min en `bootstrap/app.php` (`withoutOverlapping`, en segundo plano; el
scheduler corre como `www-data` bajo Supervisor). Estado en `storage/app/security/`
(`code_index.json`, `uploads_index.json`, `state.json`; directorio `2770 maxi:www-data`).
Log en el canal `security` → `storage/logs/security-AAAA-MM-DD.log` (90 días).

### Qué comprueba

**(a) Subidas y `public/`** — `storage/app/public`, `storage/app/media`, `public/` (sin seguir
enlaces simbólicos):
- nombres `.php*`, `.phtml`, `.pht`, `.phar`, `.phps`, `.cgi`, `.pl`, `.py`, `.sh`
  (también dobles extensiones como `x.php.jpg`), `.htaccess`, `.user.ini`;
- ficheros de hasta 2 MB cuyo contenido tenga `<?php` (no se lee el contenido de
  `public/build*` ni `public/vendor`, sí sus nombres);
- solo se vuelve a leer un fichero si cambió su mtime o tamaño.
- Línea base: `public/index.php` y `public/.htaccess`. Si cambian (hash), alerta.

**(b) Código PHP** — `app`, `modules`, `config`, `routes`, `bootstrap` (sin `bootstrap/cache`),
`vendor`: ficheros `.php` nuevos, modificados (hash distinto) o borrados desde la pasada
anterior. Índice mtime+size; el SHA-1 solo se recalcula si cambian (≈26.000 ficheros en <1 s).

**(c) Umbrales en los últimos 5 minutos** (valores por defecto, variables opcionales):

| Métrica | Umbral | Variable |
|---|---|---|
| 403 "ERP API: acceso denegado" (IP no permitida) | ≥ 10 | `SECURITY_WATCH_ERP_403` |
| 401 de la API ERP (credenciales) | ≥ 30 | `SECURITY_WATCH_ERP_401` |
| Logins fallidos en total (`failed`, `lockout`, `2fa_failed`) | ≥ 20 | `SECURITY_WATCH_FAILED_LOGINS` |
| Logins fallidos desde una misma IP | ≥ 10 | `SECURITY_WATCH_FAILED_LOGINS_IP` |
| Logins fallidos contra una misma cuenta | ≥ 8 | `SECURITY_WATCH_FAILED_LOGINS_EMAIL` |

Las denegaciones de la API ERP no se pueden leer de un fichero (`LOG_CHANNEL=stderr`): se cuentan
al vuelo con el evento `MessageLogged` (`App\Support\Security\SecurityCounters`, cubos por
minuto en el Redis `limiter`) y se copian (máx. 20/min) al log `security`. Los logins fallidos
salen de la tabla `login_attempts` y, como respaldo, de los eventos `Failed`/`Lockout`.

Una misma alerta de umbral no se repite antes de 30 min (`alert_cooldown_minutes`).

### A quién avisa

Usuarios con rol `super-admin`: notificación en base de datos (campana del panel) y email si el
mailer no es `log`/`array` y hay remitente (hoy `sendmail`). Envío síncrono (no depende de la
cola). Siempre queda además en el log `security`.

### Línea base y ventana de instalación

- La primera ejecución (o `php artisan security:watch --init`) guarda el estado actual **sin
  alertar**.
- Tras `--init` hay una ventana (hasta medianoche, o `--quiet-until="2026-10-01 08:00"`) en la
  que los cambios de ficheros (a/b) solo se registran en el log, sin notificar. Los umbrales (c)
  sí avisan. Se instaló el 29-sep-2026 con ventana hasta el 30-sep 00:00 para no alertar por los
  arreglos de ese día: revisa `storage/logs/security-2026-09-29.log`.
- **En cada despliegue** se generarán alertas de "Código PHP MODIFICADO" en la siguiente pasada.
  Para evitarlo, ejecuta al final del despliegue:
  `php artisan security:watch --init --quiet-until="+10 minutes"` (como `maxi` vale: el
  directorio de estado es del grupo `www-data` con setgid).
- `--dry-run` muestra los hallazgos sin notificar ni guardar el índice.

---

## 5. Auditoría antes de cada merge: `php artisan security:audit`

Ejecuta las comprobaciones del apartado 16 de la guía (búsquedas 1-6 en `modules/` y `app/` y
la lista de rutas públicas) y compara con:

- `docs/seguridad/auditoria_linea_base.json` — resultados aceptados de 1-6 (fichero + contenido
  de la línea, no el número de línea);
- `docs/seguridad/rutas_publicas_aprobadas.txt` — rutas sin autenticación aprobadas.

Sale con código **1** si hay resultados nuevos o rutas públicas no aprobadas (sirve en CI o en
un hook de pre-merge). Opciones: `--details` (lista todo), `--update-baseline` y
`--update-routes` (tras revisar y justificar en el PR).
