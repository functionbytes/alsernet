# Attention

> Sistema peticiones (Peticiones, Quejas, Reclamos, Sugerencias, Felicitaciones) simplificado

## Proposito

Gestiona el ciclo de vida completo de peticiones ciudadanas (PQRSF): recepcion publica sin autenticacion, asignacion a departamentos y agentes, seguimiento con SLA, comunicacion via email, y reportes estadisticos. Soporta radicado automatico y seguimiento publico por numero de radicado.

## Componentes principales

- **Modelos**:
  - `Attention` — peticion principal con radicado, estado, tipo, departamento y SLA
  - `AttentionType` — tipos de peticion (Peticion, Queja, Reclamo, Sugerencia, Felicitacion)
  - `AttentionDepartment` — departamentos receptores con agentes asignados
  - `AttentionSede` — sedes o puntos de atencion geograficos
  - `AttentionNote` — notas internas por peticion (no visibles al ciudadano)
  - `AttentionAction` — historial de acciones realizadas sobre una peticion
  - `AttentionSlaPolicy` / `AttentionSlaBreach` — politicas SLA y registros de incumplimiento
  - `AttentionSatisfaction` — encuestas de satisfaccion post-cierre
  - `AttentionRoutingRule` — reglas automaticas de enrutamiento

- **Rutas principales**:
  - `GET /peticiones` — formulario publico de radicacion (sin auth)
  - `POST /peticiones` — envio del formulario publico (throttle 10/min)
  - `GET /peticiones/tracking/{radicado}` — seguimiento publico por radicado
  - `GET /panel/attentions` — listado de peticiones pendientes (panel)
  - `GET /panel/attentions/dashboard` — dashboard analitico
  - `POST /panel/attentions/bulk-action` — acciones masivas (AJAX)
  - `GET /panel/attentions/export` — exportacion de peticiones
  - `GET /panel/settings/attention` — configuracion del modulo

- **Servicios**:
  - `AttentionRoutingService` — asignacion automatica segun reglas de enrutamiento
  - `AttentionNotificationService` — notificaciones a agentes y ciudadanos
  - `AttentionStatisticsService` — calculo de estadisticas y reportes
  - `AttentionExportService` — generacion de exportaciones Excel/CSV
  - `AttentionEmailTemplateService` — renderizado de plantillas de email variables
  - `AttentionBulkActionService` — procesamiento de acciones masivas
  - `BusinessDaysService` — calculo de dias habiles (excluye festivos colombianos)

- **Jobs**:
  - `CheckAttentionSlaBreachesJob` — verifica incumplimientos de SLA programado
  - `ExportAttentionsJob` — genera exportaciones en background
  - `MailTemplateJob` — envia emails de notificacion desde plantillas

## Permisos (Spatie)

| Permiso | Descripcion |
|---------|-------------|
| `attention.view` | Ver peticiones asignadas o del departamento |
| `attention.view-all` | Ver todas las peticiones sin restriccion |
| `attention.create` | Crear nuevas peticiones |
| `attention.update` | Actualizar peticiones |
| `attention.delete` | Eliminar peticiones |
| `attention.manage` | Gestion completa del modulo |
| `attention.assign` | Asignar a usuarios o departamentos |
| `attention.change-status` | Cambiar estado |
| `attention.resolve` | Resolver peticiones |
| `attention.close` | Cerrar peticiones |
| `attention.send-email` | Enviar emails relacionados |
| `attention.manage-notes` | Gestionar notas internas |
| `attention.view-history` | Ver historial completo |
| `attention.manage-departments` | Gestionar departamentos |
| `attention.manage-types` | Gestionar tipos de peticion |
| `attention.manage-settings` | Gestionar configuracion |
| `attention.view-reports` | Ver reportes y estadisticas |

**Roles predefinidos**: `attention-settings`, `attention-manager`, `attention-agent`, `attention-user`

## Dependencias

- **Requeridos**: `Modules\Core\Models\Setting`, `Modules\Theme\Services\NavService`
- **Opcionales**: modulo de email para plantillas de notificacion

## Comandos Artisan

```bash
php artisan module:seed Attention --class=AttentionPermissionsSeeder
php artisan module:seed Attention --class=AttentionDemoDataSeeder
php artisan module:seed Attention --class=AttentionSlaPoliciesSeeder
```
