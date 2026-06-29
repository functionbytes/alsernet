# Test end-to-end PrestaShop local ↔ Engagement

Guía paso a paso para instalar y probar el módulo PrestaShop apuntando a tu panel Engagement local (Herd).

## Estado actual del entorno

| Servicio | URL | Credenciales |
|----------|-----|--------------|
| **PrestaShop frontend** | http://localhost:8888/ | (público) |
| **PrestaShop admin** | http://localhost:8888/admin569hs29j36i1co02guf/ | admin@prestashop.local · Admin123! |
| **Panel Engagement (Herd)** | https://system.test/ | tu cuenta del panel |
| **Mailpit (mails de prueba)** | http://localhost:8025/ | — |
| **PrestaShop docker** | container `prestashop_app` | network: bridge |

## Pre-requisito ya hecho automáticamente

Añadí al `/etc/hosts` del contenedor PrestaShop la entrada:
```
192.168.65.254 system.test
```
Esto permite que los webhooks server-side desde PrestaShop alcancen Herd vía `system.test`.

El módulo PrestaShop también detecta automáticamente que `*.test`/`localhost` son endpoints de testing y desactiva la verificación TLS (necesario para certificados auto-firmados de Herd).

## Paso 1 — Crear inbox + canal Web en panel Engagement

1. Abrir https://system.test/panel/settings/helpdesk-inboxes (Helpdesk Inboxes)
2. Crear inbox tipo **Web** (livechat)
3. Anotar el **Website Token** generado

## Paso 2 — Crear PlatformIntegration

1. Abrir https://system.test/panel/settings/engagement/platforms/page
2. **Nueva integración**:
   - Inbox: el creado en paso 1
   - Plataforma: **PrestaShop**
   - Store URL: `http://localhost:8888`
3. Guardar — **anotar el Integration ID y Webhook Secret** (solo se muestran 1 vez)

## Paso 3 — Subir módulo a PrestaShop

```bash
# El zip ya está construido aquí:
ls modules/Engagement/distributions/prestashop/alsernet_chat-1.0.0.zip
```

1. Login en http://localhost:8888/admin569hs29j36i1co02guf/
2. **Módulos → Catálogo → Subir un módulo**
3. Arrastrar `alsernet_chat-1.0.0.zip` o seleccionar
4. Esperar instalación
5. Click **Configurar**

## Paso 4 — Configurar el módulo

Rellenar:

| Campo | Valor |
|-------|-------|
| URL de la API | `https://system.test` |
| Website token | (del paso 1) |
| Integration ID | (del paso 2) |
| Webhook secret | (del paso 2) |
| Sincronizar catálogo | ON |

Guardar.

## Paso 5 — Verificar SDK en frontend

1. Abrir http://localhost:8888/ (frontend)
2. **DevTools → Network**:
   - Debe haber `GET https://system.test/build-engagement/sdk.js` → 200
   - Debe haber `POST https://system.test/eng/api/sdk/init` → 200
3. **DevTools → Console** → escribir:
   ```js
   chat.platform.current()  // debe devolver "prestashop"
   chat.platform.cart()     // debe devolver el carrito actual
   ```

## Paso 6 — Verificar tracking en panel

1. Navegar varias páginas en el frontend (productos, categorías, agregar al carrito)
2. Abrir https://system.test/panel/engagement/live-visitors → debe aparecer la sesión activa
3. Abrir https://system.test/panel/engagement/analytics → ver eventos por día actualizándose

## Paso 7 — Probar webhook (compra)

1. Hacer un pedido test en el frontend (cualquier producto, cualquier método de pago test)
2. Verificar en https://system.test/panel/settings/engagement/webhook-logs/page:
   - Status `processed` (verde) → todo OK
   - Status `dead` o `failed` → hay error, revisar `last_error`

## Paso 8 — Probar catálogo sync

```bash
# Ejecutar manualmente desde tu shell
SECRET="el_secret_del_paso_2"
curl -X POST \
    -H "X-Alsernet-Cron-Secret: $SECRET" \
    "http://localhost:8888/modules/alsernet_chat/cron.php?page=1"
```

Debe devolver `{"success":true,"count":N}`.

Verificar en BD: la tabla `engagement_catalog_products` debe tener N filas.

## Paso 9 — Probar trigger automático

1. En panel: https://system.test/panel/settings/engagement/triggers/page
2. **Nueva regla**:
   - Nombre: "Open chat hot visitor"
   - Condiciones: `score >= 60`
   - Acción: `open_chat`
3. En el frontend, navegar agresivamente para subir el score (5+ páginas, scroll, add to cart)
4. Cuando el score cruce 60 → el widget debe abrirse automáticamente

## Paso 10 — Probar Remarketing audiences

```bash
# Endpoint: visitantes "hot" en últimas 24h
curl -s "https://system.test/panel/remarketing/engagement-segments/hot?hours=24" \
    -H "Cookie: laravel_session=TU_SESSION"
```

## Troubleshooting

| Síntoma | Causa | Solución |
|---------|-------|----------|
| SDK 404 en DevTools | Build no hecho | `cd modules/Engagement && npm run build` |
| `init` 401 | Token inválido | Revisar token del canal Web |
| Webhook `dead` | Secret no coincide | Reconfigurar módulo PrestaShop |
| Container no alcanza Herd | falta entrada `/etc/hosts` | `docker exec --user root prestashop_app sh -c 'echo "192.168.65.254 system.test" >> /etc/hosts'` |
| TLS error en webhook | Certificado Herd no aceptado | El módulo ya hace bypass auto en `*.test` — revisar versión del .zip |

## Reset rápido para nuevo test

```bash
# Limpiar eventos engagement
docker exec prestashop_mysql mysql -uroot -p$(grep MYSQL_ROOT_PASSWORD docker-compose.yml | cut -d':' -f2 | tr -d ' ') -e "
TRUNCATE TABLE engagement_events;
TRUNCATE TABLE engagement_visitor_scores;
TRUNCATE TABLE engagement_visitor_contexts;
TRUNCATE TABLE engagement_visitor_sessions;
TRUNCATE TABLE engagement_webhook_logs;
" 2>/dev/null

# O si usas la DB de Herd:
mysql system -e "TRUNCATE TABLE engagement_events; TRUNCATE TABLE engagement_visitor_scores;"
```
