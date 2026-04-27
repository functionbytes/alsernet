# ADR 0001: Mailer vs Mailrelay — coexistencia y futura consolidacion

- **Estado**: Aceptado — Opcion C (status quo + documentar fronteras)
- **Fecha**: 2026-04-27
- **Fecha aceptacion**: 2026-04-27
- **Decisor**: arquitectura

---

## Contexto

El proyecto Alsernet cuenta con dos modulos de email que evolucionaron de forma independiente y hoy presentan solapamiento de responsabilidades.

**Modulo Mailer** (`modules/Mailer/`) nacio como la capa de email transaccional del sistema: plantillas con soporte multi-idioma (i18n), variables de reemplazo, componentes HTML reutilizables, endpoints HTTP para envio programatico y webhooks de Mailrelay (bounce, unsubscribe, complaint). Tiene estructura limpia, sigue las convenciones del proyecto (5 controllers, 9 modelos i18n, 4 services, 3 jobs, 12 migraciones aplicadas, ~17 tests).

**Modulo Mailrelay** (`modules/Mailrelay/`) nacio como integracion directa con la API de Mailrelay para email marketing. Con el tiempo crecio hasta convertirse en una plataforma multi-provider que soporta Mailrelay, Mailtrap, SendGrid, AWS SES y Postmark. Maneja subscribers, listas, campanas, automatizaciones, validacion de emails (9 validators), importaciones masivas y estadisticas. Tiene 36 controllers, ~47 services (~3540 lineas), 38 entidades, 30 migraciones aplicadas y 33 en el directorio `acas/` sin aplicar. Recientemente fue limpiado: se eliminaron scaffolding de Auth propio, sub-modulos huerfanos y 22 inline validates pendientes.

**El overlap concreto** esta en cuatro areas de negocio que ambos modulos implementan de forma paralela:

| Concepto | Mailer | Mailrelay |
|---|---|---|
| Templates | `MailerTemplate` + i18n + versioning | `EmailTemplate` + `TemplateService` |
| Variables | `MailerVariable` + `MailerVariableLang` | `MailrelayVariable` + `VariableReplacementService` |
| Components (HTML) | `MailerLayout` / `MailerComponent` | `mailrelay_layouts` (migracion 2026_01_25) |
| Endpoints de envio | `MailerEndpoint` + `SendEndpointEmailJob` | `MailrelayEndpoint` + `EndpointExecutionService` |

Ademas, Mailrelay tiene entidades que no existen en Mailer y que pertenecen exclusivamente al dominio de marketing: Subscribers, Lists, Campaigns, Automations, CampaignAnalytics, ImportJobs, EmailValidation, MediaFiles/Folders.

---

## Decision

**No se ha tomado todavia.** Este ADR documenta las opciones disponibles con sus pros y contras para que el equipo pueda decidir en el proximo ciclo de planificacion.

---

### Opcion A: Consolidar en un unico modulo "Mail"

Migrar toda la funcionalidad de Mailrelay (multi-provider, campanas, automatizaciones, subscribers) dentro del modulo Mailer, renombrarlo a `Mail` y eliminar el modulo Mailrelay.

**Pros:**
- Single source of truth para Templates, Variables, Components y Endpoints.
- Elimina ~3500 lineas de servicios duplicados (TemplateService, VariableReplacementService, EndpointExecutionService).
- Resultado final mas limpio y alineado con las convenciones del proyecto (estructura Mailer es la referencia correcta).
- Un solo namespace `Modules\Mail\` para toda la logica de correo.

**Contras:**
- Refactor masivo: 36 controllers + 38 entidades + 70 vistas + 30 migraciones aplicadas a reorganizar.
- Las 33 migraciones en `acas/` incluyen tablas criticas (`media_files`, `media_folders`, `bounce`, `api_batches`) que requieren evaluacion individual antes de decidir si se aplican o se descartan.
- La tabla `media_files` existe en BD (referenciada por `MediaFileService` activo); requiere migracion con cuidado.
- Riesgo alto de romper funcionalidad de marketing durante el refactor.
- Los 5 providers de email (`MailrelayProvider`, `AwsSesProvider`, `SendGridProvider`, `MailtrapProvider`, `PostmarkProvider`) deben trasladarse junto con el `ProviderManager` y sus contratos.
- Estimado: 2-3 sprints completos con regresiones probables.

---

### Opcion B: Mantener Mailer + reducir Mailrelay a dominio de marketing

Reasignar responsabilidades con una separacion de concerns explicitica:

- **Mailer**: UNICO source of truth para Templates, Variables, Components y Endpoints transaccionales. Los modelos de Mailrelay que duplican estos conceptos (`EmailTemplate`, `mailrelay_variables`, `mailrelay_layouts`, `mailrelay_endpoints`) se migran al modulo Mailer o se eliminan.
- **Mailrelay**: SOLO el dominio de marketing/automatizacion: Subscribers, Lists, Campaigns, Automations, CampaignAnalytics, EmailValidation, Import, MediaFiles y la capa multi-provider (5 providers). El alias puede mantenerse o renombrarse a `mailmarketing` opcionalmente.

La migracion de Templates/Variables/Components de Mailrelay hacia Mailer puede hacerse de forma incremental: una entidad por sprint, sin downtime.

**Pros:**
- Separation of concerns clara y sostenible: email transaccional (Mailer) vs email marketing (Mailrelay).
- Mailer no necesita manejar 5 providers distintos ni logica de campanas — mantiene su simplicidad.
- Reutiliza la infraestructura multi-provider ya construida y probada en Mailrelay.
- El webhook controller de Mailer (`MailrelayWebhookController`) ya es el punto de entrada correcto para eventos de bounce/unsubscribe — no cambia.
- Migracion incremental: bajo riesgo por sprint.
- Estimado: 1-2 sprints para la separacion inicial, mas sprints opcionales para cleanup del naming.

**Contras:**
- Requiere migrar los Templates de Mailrelay hacia Mailer (tablas `email_templates` → `mailer_templates`), incluyendo datos existentes en BD.
- Eliminar duplicados de Variables y Components implica actualizar todas las referencias en services y controllers de Mailrelay.
- Si se renombra el modulo, el rename masivo (`Mailrelay` → `MailMarketing` en namespace, rutas y permisos) es tedioso aunque mecanico.
- Dos modulos de email siguen existiendo; los developers nuevos deben leer la documentacion para saber cual usar.

---

### Opcion C: Status quo con fronteras documentadas

Mantener ambos modulos tal como estan, sin refactor. Documentar reglas claras de uso:

- **Mailer**: emails de sistema (notificaciones de plataforma, confirmaciones, reset de password, alertas automaticas).
- **Mailrelay**: emails masivos (newsletters, campanas de marketing, automatizaciones de ciclo de vida).
- Cada modulo mantiene sus propias Templates/Variables/Components con prefijos de namespace que evitan colision (`mailer_*` vs `mailrelay_*`).
- Webhooks compartidos via el `MailrelayWebhookController` de Mailer que ya existe.

**Pros:**
- Cero refactor inmediato; equipos pueden seguir trabajando en paralelo sin conflictos.
- Las entidades duplicadas son funcionalmente independientes: no hay riesgo de romper nada.
- Documentacion de fronteras es suficiente para equipos que conocen el sistema.

**Contras:**
- La duplicacion esta documentada pero sigue siendo duplicacion: cualquier bug en la logica de Template rendering debe corregirse en dos lugares.
- Confusion para desarrolladores nuevos: "en cual modulo creo este template de bienvenida?".
- El modulo Mailrelay tiene 22 inline validates aun pendientes de extraer a Form Requests — ese cleanup deberia hacerse de todos modos.
- Las 33 migraciones `acas/` sin aplicar son deuda tecnica activa: no se sabe si son necesarias o si generan conflictos con las migraciones aplicadas.

---

## Recomendacion del documento

**Opcion B** es la mas balanceada para el estado actual del proyecto.

Preserva las fortalezas de cada modulo (Mailer: simple, limpio, transaccional; Mailrelay: multi-provider, orientado a marketing) sin requerir un refactor masivo de riesgo alto. La eliminacion de los conceptos duplicados (Templates, Variables, Components, Endpoints en Mailrelay) puede planificarse como un backlog de cleanup incremental. El resultado final es una separacion de concerns que cualquier desarrollador puede entender con una sola frase: "Mailer envia; Mailrelay gestiona campanas."

La Opcion A es preferible a largo plazo si el equipo tiene capacidad de refactor sostenida, pero el estado actual de Mailrelay (38 entidades, 33 migraciones sin evaluar) hace que el riesgo sea alto en este momento.

La Opcion C no resuelve la deuda tecnica y la perpetua.

---

## Acciones siguientes

1. El equipo decide entre Opcion A, B o C en el proximo standup o sesion de planificacion.
2. **Si Opcion B**: crear ADR-0002 con el plan de migracion detallado (secuencia de entidades a mover, strategy de migracion de datos, compatibilidad con BD existente).
3. **Si Opcion A**: crear ADR-0002 con la secuencia de eliminacion de Mailrelay (orden de controllers a mover, plan para las migraciones `acas/`).
4. **Si Opcion C**: actualizar `modules/Mailer/README.md` y `modules/Mailrelay/README.md` con las fronteras de responsabilidad documentadas, y crear un backlog item para las 33 migraciones `acas/` pendientes.
5. **Independiente de la opcion elegida**: resolver las 33 migraciones `acas/` de Mailrelay (aplicar, descartar o convertir en migraciones normales).

---

## Estado actual del proyecto (referencia rapida)

| Modulo | Controllers | Servicios | Modelos/Entidades | Migraciones | Tests |
|---|---|---|---|---|---|
| Mailer | 5 | 4 | 9 (i18n) | 12 aplicadas | ~17 |
| Mailrelay | 36 | 47 (~3540 lineas) | 38 entidades | 30 aplicadas + 33 en `acas/` | 10 |

**Deuda tecnica activa en Mailrelay:**
- 33 migraciones en `acas/` sin aplicar (status desconocido respecto a la BD actual).
- `personal_access_tokens` creada por Mailrelay (`2026_01_15_232525`) puede colisionar con Sanctum.
- Inline validates pendientes de convertir a Form Requests (en proceso de cleanup).

---

## Implementacion de Opcion C

### Acciones aplicadas (2026-04-27)

1. READMEs actualizados con responsabilidades claras
2. Tabla comparativa Mailer vs Mailrelay documentada en ambos modulos
3. Casos de uso explicitos por modulo

### Reglas para developers nuevos

#### Cuando usar Mailer

- Email triggered por evento de Laravel (`Event::dispatch`)
- Notificacion a un unico usuario
- Template con variables del modelo (User, Order, etc.)
- Idioma del email = idioma del user (i18n)

#### Cuando usar Mailrelay

- Newsletter a una lista (>100 destinatarios)
- Campana con segmentacion
- Email con tracking pixel y click tracking
- A/B testing
- Multi-provider routing (failover entre proveedores)

### Re-evaluacion

Re-evaluar esta decision en **2027-Q2** segun:
- Crecimiento del proyecto
- Feedback de developers
- Costo de mantenimiento
- Si Mailrelay sigue acumulando deuda tecnica

---

## Tags

`#email`, `#architecture`, `#deuda-tecnica`, `#consolidacion`, `#mailrelay`, `#mailer`
