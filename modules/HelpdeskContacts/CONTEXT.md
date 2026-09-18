# HelpdeskContacts

CRM 360 de contactos del Helpdesk. Vista de lista/detalle (split) y una pestaña-shell
360 por cliente que agrega datos de varias fuentes vía endpoints JSON cargados de forma
perezosa (lazy-load).

## Identidad

- **Name**: `HelpdeskContacts` · **alias**: `contacts` · **namespace**: `Modules\HelpdeskContacts`
- **Provider**: `Modules\HelpdeskContacts\Providers\HelpdeskContactsServiceProvider`
- **View namespace**: `contacts::` (`resources/views`)
- **PSR-4**: `Modules\HelpdeskContacts\` → `modules/HelpdeskContacts/app/`

## Rutas (`routes/web.php`)

Montadas con prefix `panel/helpdesk/contacts` (redirect 301 desde el antiguo `panel/contacts`), name `contacts.`, middleware `['web','auth','can:contacts.view']`.
`{customer}` es el id de `helpdesk_customers` (binding `Modules\Helpdesk\Models\Customer`,
conexión `helpdesk`).

| Método | URI | Acción | Nombre |
|--------|-----|--------|--------|
| GET | `/` | `ContactsController@index` | `contacts.index` |
| GET | `/{customer}` | `ContactsController@show` | `contacts.show` |
| GET | `/{customer}/tab/resumen` | `ContactTabsController@resumen` | `contacts.tab.resumen` |
| GET | `/{customer}/tab/conversaciones` | `ContactTabsController@conversaciones` | `contacts.tab.conversaciones` |
| GET | `/{customer}/tab/chats` | `ContactTabsController@chats` | `contacts.tab.chats` |
| GET | `/{customer}/tab/erp` | `ContactTabsController@erp` | `contacts.tab.erp` |
| GET | `/{customer}/tab/prestashop` | `ContactTabsController@prestashop` | `contacts.tab.prestashop` |
| GET | `/{customer}/tab/tienda` | `ContactTabsController@tienda` | `contacts.tab.tienda` |
| GET | `/{customer}/tab/actividad` | `ContactTabsController@actividad` | `contacts.tab.actividad` |
| POST | `/{customer}/sync` | `ContactTabsController@sync` (`can:contacts.update`) | `contacts.sync` |

Todas las rutas de pestaña devuelven JSON con la forma `{ "success": true, "data": {...} }`
(claves camelCase). Cuando un módulo está apagado o no hay datos: 200 con `data` vacía y
`"available": false`.

## Permisos (Spatie, guard `web`)

`contacts.view`, `contacts.update`, `contacts.commerce`, `contacts.insights`, `contacts.merge`.
Seeder: `HelpdeskContactsPermissionsSeeder`.

## Módulos opcionales (guards obligatorios)

El panel Helpdesk no debe romperse si un módulo está apagado. Antes de importar/llamar
servicios opcionales, comprobar `Module::find('X')?->isEnabled()` y `class_exists(FQCN)`:

- **ERP**: `HelpdeskErp` + `ErpContextService` + `extension_loaded('oci8')`
- **PrestaShop**: `HelpdeskPrestashop` + `PrestashopContextService`
- **Social** (deshabilitado): `HelpdeskSocial` → sin chats sociales por defecto
- **Livechat**: `HelpdeskLivechat` + `WidgetSession`
- **Tickets**: helper `helpdesk_tickets_enabled()` o `Module::find('HelpdeskTickets')`
- **Remarketing** (tienda local por email): `Module::find('Remarketing')`

Cuando está apagado → sección vacía con `available:false`. Nunca importar una clase de
módulo opcional en la cabecera del archivo sin fallback `class_exists`.

## Servicios reutilizados (no reimplementar)

- `Modules\Helpdesk\Services\CustomerInsightsService`: `healthScore`, `lifetimeMetrics`, `journeyTimeline`
- `Modules\Helpdesk\Models\Customer`: `conversations()`, `pageVisits()`, `externalIdFor()`, `getAvatarUrl()`, `getAllCustomAttributes()`
- `Modules\HelpdeskErp\Services\ErpContextService::getCustomerContext($email, $phone, $customerId)`
- `Modules\HelpdeskPrestashop\Services\PrestashopContextService::getCustomerContext($email)`
- Remarketing por email: ver `modules/Helpdesk/app/Http/Controllers/Managers/CustomerEcommerceController.php`
- Livechat: `Modules\HelpdeskLivechat\Models\WidgetSession`

## Frontend

Bootstrap 5.3 + jQuery + AJAX. Vistas `@extends('layouts.theme')`. Font Awesome 6.
Color primario `#90bb13`. Tabs Bootstrap con lazy-load por pane. `contacts-360.js` en
`resources/assets/js/`. Contrato DOM: root `#contact360[data-customer-id][data-base-url]`,
botones `[data-contact-tab]`, panes `#pane-{tab}[data-loaded]`, botón sync `#contact-sync-btn`.

> Los controllers, servicios, vistas y seeder son creados por otros agentes. Este módulo
> declara la estructura y las rutas.
