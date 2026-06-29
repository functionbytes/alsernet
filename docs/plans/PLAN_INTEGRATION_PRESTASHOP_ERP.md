# Plan: Integración multi-fuente para ficha de cliente en Helpdesk

> **Fecha:** 2026-05-02
> **Estado:** Pendiente de aprobación
> **Módulos afectados:** `Engagement`, `Helpdesk`
> **Proyectos involucrados:** `system` (este), `manager` (ERP, en Docker)

---

## Contexto

El panel de Helpdesk debe mostrar al agente toda la información que existe sobre un cliente cuando abre una conversación: pedidos, devoluciones, vales, deudas, direcciones, etc. Hoy esos datos solo llegan vía webhooks pasivos o vía la identidad que envía el widget (cuando el cliente está logueado en la web).

Al mismo tiempo, **distintos clientes del sistema usan combinaciones diferentes** de plataformas:

| Cliente | Plataformas |
|---------|-------------|
| Genérico A | PrestaShop |
| Genérico B | Shopify |
| Genérico C | WooCommerce |
| **Alvarez** | **PrestaShop + ERP propio (Oracle, en otro proyecto Docker)** |

Caso Alvarez es especial: el ERP es la **fuente de verdad en tiempo real**. PrestaShop refleja una sincronización eventual desde el ERP. El agente que atiende un chat debe ver el estado de gestión (ERP) **y** el estado web (PrestaShop) lado a lado.

---

## Fuentes de datos disponibles

### A) PrestaShop
- **Ubicación:** Plugin `alsernet_chat` instalado en la tienda.
- **Acceso actual:** Webhooks push (`actionValidateOrder`, `actionCustomerAccountAdd`, `actionCartUpdateQuantityBefore`).
- **Acceso propuesto:** Endpoint custom `api.php` dentro del plugin para pull on-demand.
- **Datos disponibles:** customer, orders, order lines, returns, cart_rules (vouchers), addresses, customer_messages, carts.

### B) ERP Alvarez (otro proyecto)
- **Ubicación:** `/Users/developerts/Herd/manager/src/modules/Erp` — Laravel modular en Docker, BD Oracle.
- **Acceso:** API REST ya construida en `/api/erp/customer/...`
- **Auth:** Sanctum token (público según `routes/api.php`, sin `auth:sanctum` en la ruta principal).
- **Datos disponibles** (cada uno como endpoint separado):
  - `GET /api/erp/customer/search` — búsqueda por email/teléfono/CIF
  - `GET /api/erp/customer/{id}` — resumen
  - `GET /api/erp/customer/{id}/personal` — datos personales
  - `GET /api/erp/customer/{id}/lopd` — consentimientos LOPD
  - `GET /api/erp/customer/{id}/addresses` — direcciones
  - `GET /api/erp/customer/{id}/contact` — contacto
  - `GET /api/erp/customer/{id}/cards` — tarjetas
  - `GET /api/erp/customer/{id}/accounts` — cuentas bancarias
  - `GET /api/erp/customer/{id}/catalogs` — catálogos asignados
  - `GET /api/erp/customer/{id}/quotas` — cuotas
  - `GET /api/erp/customer/{id}/orders` — pedidos
  - `GET /api/erp/customer/{id}/orders/{orderId}` — detalle pedido
  - `GET /api/erp/customer/{id}/delivery-notes` — albaranes
  - `GET /api/erp/customer/{id}/delivery-notes/{deliveryId}` — detalle albarán
  - `GET /api/erp/customer/{id}/invoices` — facturas
  - `GET /api/erp/customer/{id}/invoices/{invoiceId}` — detalle factura
  - `GET /api/erp/customer/{id}/payments` — cobros
  - `GET /api/erp/customer/{id}/debts` — deudas
  - `GET /api/erp/customer/{id}/balance` — balance
  - `GET /api/erp/customer/{id}/vouchers` — vales
  - `GET /api/erp/customer/{id}/bonuses` — bonos
  - `GET /api/erp/customer/{id}/loyalty-points` — puntos fidelización

### C) Shopify (futuro)
- API GraphQL Admin / REST Admin con access token de la app instalada.

### D) WooCommerce (futuro)
- API REST `/wp-json/wc/v3/` con consumer key/secret.

---

## Diseño unificado

### Patrón: **Connector abstracto + multi-fuente**

Una sola interfaz, múltiples implementaciones. El right-panel pregunta "¿qué integraciones tiene este inbox?" y consulta a todas en paralelo, mostrando los datos agrupados por origen.

```
┌─────────────────────────────────────────────────────────────┐
│  Right-panel del inbox                                      │
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │   PrestaShop │  │   ERP        │  │   Shopify    │       │
│  │   (webhooks  │  │   (Oracle    │  │   (Admin     │       │
│  │   + plugin   │  │   real-time) │  │   API)       │       │
│  │   api.php)   │  │              │  │              │       │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘       │
│         │                 │                 │               │
│         ▼                 ▼                 ▼               │
│   ┌──────────────────────────────────────────────────┐      │
│   │  CustomerDataConnector (interface común)         │      │
│   │   - profile()                                    │      │
│   │   - orders() / orderDetail()                     │      │
│   │   - returns() / vouchers() / addresses()         │      │
│   │   - debts() / payments() / balance() (ERP only)  │      │
│   │   - loyaltyPoints() / bonuses() (ERP only)       │      │
│   └──────────────────────────────────────────────────┘      │
│         │                 │                 │               │
│         ▼                 ▼                 ▼               │
│   ConnectorOrchestrator (paralelo, cache 5min)              │
│         │                                                   │
│         ▼                                                   │
│   Right-panel: tabs por fuente + tabs combinados            │
└─────────────────────────────────────────────────────────────┘
```

### Tabla `engagement_platform_integrations`

Ya existe y soporta este patrón. Solo añadir `'erp'` a las plataformas reconocidas. La columna `config` (json) almacena lo específico de cada plataforma:

```json
// PrestaShop (existente)
{
  "platform": "prestashop",
  "store_url": "https://shop.alvarez.com",
  "webhook_secret": "..."
}

// ERP (nuevo)
{
  "platform": "erp",
  "store_url": "https://erp.alvarez.local/api/erp",
  "config": {
    "auth_type": "sanctum",
    "auth_token": "encrypted...",
    "lookup_strategy": "email",
    "external_id_format": "numeric"
  }
}
```

---

## Plan unificado en 3 fases

### Fase 1 — PrestaShop pull on-demand (anterior, sin cambios)

Implementar lo planeado por el agente Plan en la sesión anterior:

| # | Componente | Archivo | Agente | Complejidad |
|---|-----------|---------|--------|-------------|
| 1.1 | Migración `external_id` + `external_platform` en `helpdesk_customers` | `modules/Helpdesk/database/migrations/...` | database | S |
| 1.2 | Customer model `$fillable` + scope `byExternalId()` | `modules/Helpdesk/app/Models/Customer.php` | backend | S |
| 1.3 | Mejorar `PlatformWebhookHandler::resolveCustomer` (email → external_id → autocreate) + cache invalidation | `modules/Engagement/app/Services/PlatformWebhookHandler.php` | backend | S |
| 1.4 | Plugin PS — endpoint `api.php` con 9 acciones (profile, orders, returns, vouchers, cart, messages, addresses, order.detail, order.start_return) | `modules/Engagement/distributions/prestashop/alsernet_chat/api.php` | backend (PHP standalone) | L |
| 1.5 | Plugin PS — bump 1.1.0, hooks mejorados (`actionCartSave`, `actionObjectCustomerUpdateAfter`, payload de orden con líneas) | `alsernet_chat.php` | backend | S |

### Fase 2 — Capa abstracta de connectors (NUEVO)

| # | Componente | Archivo | Agente | Complejidad |
|---|-----------|---------|--------|-------------|
| 2.1 | Interfaz `CustomerDataConnector` con métodos estandarizados | `modules/Engagement/app/Contracts/CustomerDataConnector.php` | backend | S |
| 2.2 | `PrestaShopCustomerConnector` implementa interfaz, llama al `api.php` del plugin con HMAC | `modules/Engagement/app/Connectors/PrestaShopCustomerConnector.php` | backend | M |
| 2.3 | `ErpCustomerConnector` implementa interfaz, llama a `/api/erp/customer/...` del proyecto manager con bearer token | `modules/Engagement/app/Connectors/ErpCustomerConnector.php` | backend | M |
| 2.4 | `CustomerDataOrchestrator` — descubre integraciones activas para inbox y consulta en paralelo | `modules/Engagement/app/Services/CustomerDataOrchestrator.php` | backend | M |
| 2.5 | `ConnectorFactory` — factory para resolver el connector según `platform` | `modules/Engagement/app/Connectors/ConnectorFactory.php` | backend | S |
| 2.6 | Cache compartido: `Cache::remember("conn.{$platform}.{$integrationId}.{$action}.{$lookupHash}", 300, ...)` | (en cada connector) | backend | inline |

### Fase 3 — Endpoint proxy + UI multi-fuente

| # | Componente | Archivo | Agente | Complejidad |
|---|-----------|---------|--------|-------------|
| 3.1 | `IntegrationLookupRequest` Form Request — valida action, lookup, force | `modules/Engagement/app/Http/Requests/IntegrationLookupRequest.php` | backend | S |
| 3.2 | `IntegrationLookupController` — POST `/panel/engagement/customer-data/lookup` con `web,auth` middleware. Recibe lookup + acción, descubre integraciones del inbox, devuelve `{ prestashop: {...}, erp: {...} }` agrupado por fuente | `modules/Engagement/app/Http/Controllers/Managers/IntegrationLookupController.php` | backend | M |
| 3.3 | Ruta nueva en `modules/Engagement/routes/managers.php` con `can:helpdesk.conversations.view` | `routes/managers.php` | backend | inline |
| 3.4 | Right-panel — añadir contenedor con `data-integrations` (lista de plataformas activas), tabs nuevos: ERP (Pedidos+Albaranes+Facturas+Deudas+Vales+Bonos+Puntos), badge unificado | `modules/Helpdesk/resources/views/managers/inbox/partials/right-panel.blade.php` | frontend | L |
| 3.5 | JS lazy-load multi-fuente: detecta integraciones, llama `/customer-data/lookup` en paralelo por fuente, pinta resultados con plantillas. Refresh por fuente individual o global. Indicador de "fresh from real-time" para datos del ERP, "synced from web" para PrestaShop | `public/vendor/helpdesk/conversations.js` (o nuevo `customer-data.js`) | frontend | L |
| 3.6 | UI: comparación visual cuando un dato existe en ambas fuentes y discrepa (badge "⚠ pendiente sincronización") | (en JS + CSS) | frontend | M |

### Fase 4 — Tests

| # | Componente | Archivo | Agente |
|---|-----------|---------|--------|
| 4.1 | `PrestaShopCustomerConnectorTest` — Http::fake, cache, HMAC | tests/Feature | testing |
| 4.2 | `ErpCustomerConnectorTest` — Http::fake con bearer, parseo de cada endpoint | tests/Feature | testing |
| 4.3 | `CustomerDataOrchestratorTest` — múltiples integraciones activas, paralelización | tests/Feature | testing |
| 4.4 | `IntegrationLookupControllerTest` — auth, validación, multi-source response | tests/Feature | testing |
| 4.5 | `PlatformWebhookHandlerResolveCustomerTest` — los 4 casos del plan original | tests/Feature | testing |

---

## Decisiones clave que necesitan tu input

### Decisión 1: ¿Cómo identificamos al cliente en cada fuente?

El cliente se identifica diferente en cada sistema:
- **Email** — funciona en PrestaShop y ERP (campo `email` en ambos)
- **CIF/DNI** — funciona en ERP (campo `CIF`), no en PrestaShop por defecto
- **Teléfono** — funciona en ERP (tabla `CLIENTETELEFONO_CENT`), no es lookup directo en PS
- **`external_id`** — id en cada plataforma específica, no transferible

**Propuesta:** El panel guarda en `helpdesk_customers`:
- `email` (lookup primario para todas las plataformas)
- `external_id` + `external_platform` (uno por plataforma — extender a multi-platform via JSON)
- O una tabla pivot `helpdesk_customer_external_ids` con `(customer_id, platform, external_id)` — más limpio, soporta el caso Alvarez (un mismo customer tiene id en PS Y en ERP)

¿Qué prefieres?
- **A)** Columnas `external_id` + `external_platform` (simple, una sola plataforma "principal")
- **B)** Tabla pivot `helpdesk_customer_external_ids` (multi-platform, flexible) ← **recomendado para tu caso**

### Decisión 2: ¿Auth con el ERP cómo se gestiona?

El módulo ERP del manager tiene `routes/api.php` con grupo `middleware(['api'])` (público en algunos endpoints) y `auth:sanctum` en otros. Para llamadas desde nuestro panel:

**Propuesta:** Generar un token Sanctum de servicio en el proyecto `manager` (un user dedicado tipo `system@helpdesk-bridge`), guardarlo encriptado en `engagement_platform_integrations.config.auth_token`. El connector lo lee y firma con `Authorization: Bearer ...`.

¿Confirmas esta aproximación o prefieres otro mecanismo (HMAC, mTLS, IP allowlist)?

### Decisión 3: ¿Cuándo dejamos de pintar PrestaShop si tenemos ERP?

Para Alvarez, el ERP es fuente de verdad. Pero a veces hay datos que sólo están en PrestaShop (carrito web abandonado, mensajes del chat de la web). Opciones:

- **A)** Mostrar siempre ambas fuentes (tabs separados): "Pedidos web (PS)" y "Pedidos gestión (ERP)"
- **B)** Una sola lista combinada con badge de origen, y tooltip explicando: "🌐 Web · 🏢 Gestión"
- **C)** ERP por defecto, PrestaShop sólo accesible vía botón "Ver datos web" ← suele ser confuso

¿Cuál prefieres? **Mi recomendación: A para v1** (tabs separados, simple y claro). Más adelante podemos hacer B si los agentes lo piden.

### Decisión 4: ¿El proyecto `manager` necesita cambios?

Para que el panel pueda llamar al ERP necesitamos:

1. **Sí, sí o sí** — Generar token Sanctum del usuario sistema (artisan command).
2. **Probablemente** — Asegurarnos de que `routes/api.php` tiene CORS permitido para el dominio del panel Alsernet, o llamar server-to-server (que es lo que haremos).
3. **Posiblemente** — Agregar middleware de rate limiting específico para el bridge para no saturar Oracle.

¿Tengo permiso para tocar `manager`? ¿O prefieres que documente los pasos y los apliques tú manualmente?

### Decisión 5: ¿Cache TTL diferenciado por fuente?

- **PrestaShop**: 300s (la web tampoco cambia frecuentemente para un cliente)
- **ERP**: 60s o menos (es real-time, queremos datos frescos)
- **Datos financieros (deudas, balance)**: 30s (más sensible)
- **Datos personales (direcciones, LOPD)**: 1800s (cambian poco)

¿Te parece bien que cada `action` tenga su propio TTL configurable en `config/engagement.php`?

### Decisión 6: ¿Eventos de webhook del ERP?

El ERP no envía webhooks hoy. Si quisiéramos paridad con PrestaShop (para invalidar cache cuando cambia algo en gestión), habría que:
- Añadir un mecanismo en el módulo ERP (otro proyecto) que dispare webhooks.
- O hacer polling cada N minutos para órdenes recientes.
- O confiar en el TTL corto (60s) y aceptar staleness mínima.

**Recomendación:** Para v1, sólo TTL corto. Webhooks ERP los agregamos en una segunda iteración cuando los datos lo justifiquen.

---

## Riesgos identificados

| Riesgo | Mitigación |
|--------|------------|
| **El ERP está en otro proyecto y otro Docker** — no podemos importar clases | Comunicación 100% HTTP. El connector sólo conoce la API REST documentada. |
| **Oracle no es la BD del panel** — no podemos hacer JOINs | Todo lo que necesitemos lo expone el módulo ERP como endpoint. Si falta algo, se agrega allá. |
| **El módulo ERP puede cambiar su API** — versionado | Usar `/api/erp/v2/...` (ya está versionado) y validar el contrato en tests. |
| **Latencia en el panel cuando el agente abre conversación** — múltiples llamadas HTTP | Llamadas en paralelo (Guzzle pool o Laravel `Http::pool()`). Skeleton UI mientras carga. Cache agresivo. |
| **Datos sensibles del ERP** (deudas, CIF) | Auth Sanctum + permiso `helpdesk.conversations.view` no es suficiente. Crear permiso dedicado `engagement.erp.financial.view` para deudas/balance/payments. |
| **El plugin PS api.php puede no instalarse en clientes existentes** | Ship junto con el bump 1.1.0; para tiendas legacy, fallback a webhooks-only y mostrar warning en el panel de integraciones. |
| **Customer ID en cada plataforma diverge** | La tabla pivot `external_ids` (Decisión 1B) lo resuelve. |
| **Search por email cuando hay homónimos** — múltiples customers en ERP con mismo email | El endpoint `/customer/search` ya devuelve lista; el connector toma el primero o pide al agente desambiguar. |

---

## Orden de ejecución propuesto

```
SPRINT 1 (Fase 1 — PrestaShop pull on-demand)
├── DB migration external_id (o tabla pivot según Decisión 1)
├── Customer model
├── PlatformWebhookHandler.resolveCustomer
├── Plugin PS api.php (9 acciones)
└── Plugin PS hooks mejorados + version bump

SPRINT 2 (Fase 2 — Connectors abstractos)
├── Contract CustomerDataConnector
├── PrestaShopCustomerConnector (refactor del cliente HTTP)
├── ErpCustomerConnector
├── ConnectorFactory
└── CustomerDataOrchestrator

SPRINT 3 (Fase 3 — UI + Endpoint proxy)
├── IntegrationLookupRequest
├── IntegrationLookupController + ruta
├── Right-panel multi-fuente (tabs ERP + tabs PS)
└── JS lazy-load + refresh por fuente

SPRINT 4 (Fase 4 — Tests + auth ERP)
├── Generar token Sanctum en proyecto manager
├── Documentar setup en docs/integrations/erp.md
└── Suite de tests Feature (5 archivos)
```

Cada sprint es independiente y entrega valor:
- **Sprint 1** ya da pull on-demand de PrestaShop completo (resuelve el caso 80% de clientes).
- **Sprint 2** prepara el terreno para múltiples fuentes sin romper Sprint 1.
- **Sprint 3** habilita el caso Alvarez (ERP + PS).
- **Sprint 4** estabiliza y documenta.

---

## ¿Qué necesito de ti antes de empezar?

1. **Decisión 1**: ¿Columna simple o tabla pivot para external_ids? (recomiendo pivot)
2. **Decisión 2**: ¿Auth Sanctum bridge OK?
3. **Decisión 3**: ¿Tabs separados PS/ERP (recomiendo) o lista combinada?
4. **Decisión 4**: ¿Tengo permiso para tocar el proyecto `manager` para crear el token bridge?
5. **Decisión 5**: ¿TTL diferenciado por acción OK?
6. **Decisión 6**: ¿Webhooks ERP para invalidación los dejamos para v2?

Y la decisión más importante:

**¿Empezamos por Sprint 1 (PrestaShop completo) y luego encadenamos, o prefieres que diseñe Sprint 2 primero (abstracción) para no tener que refactorizar el código de Sprint 1?**

Mi recomendación: **empezar por Sprint 2 primero (la abstracción)** y luego implementar PrestaShop ya como un connector concreto. Así no escribimos código que vamos a refactorizar en una semana. La Fase 2 sólo añade ~3-4 archivos nuevos, no es overhead significativo.
