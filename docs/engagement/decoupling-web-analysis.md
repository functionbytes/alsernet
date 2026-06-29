# Análisis de impacto: Desacoplar `Web` de HelpdeskLivechat

## Contexto actual

El módulo **Engagement** depende de `Modules\HelpdeskLivechat\Models\Channels\Web` para:
- Validar `website_token` en el middleware `EnsureWebsiteToken`
- Resolver el `inbox` asociado al canal web
- Ejecutar los 12 archivos de tests del módulo

## Arquitectura de la relación polimórfica

```
helpdesk_inboxes
├── channel_type  → 'web'
├── channel_id    → ID en helpdesk_channel_webs
└── morphTo('channel') → resuelve via Inbox::CHANNEL_TYPE_MAP

Inbox::CHANNEL_TYPE_MAP = [
    'web' => Web::class,  // Modules\HelpdeskLivechat\Models\Channels\Web
    ...
]
```

La relación es **polimórfica**: `Inbox` no sabe qué tabla tiene el canal, solo sabe que `channel_type='web'` significa "busca en el modelo `Web`".

---

## Escenarios de desacoplamiento

### Opción A: Duplicar tabla y modelo (Engagement propio)

**Implementación:**
1. Crear `engagement_website_channels` con los mismos campos que `helpdesk_channel_webs`
2. Crear `Modules\Engagement\Models\WebsiteChannel`
3. Copiar todos los datos de `helpdesk_channel_webs` a `engagement_website_channels`
4. Cambiar `EnsureWebsiteToken` para usar `WebsiteChannel`
5. Actualizar tests

**¿Qué pasa si...?**

| Escenario | Impacto | Severidad |
|---|---|---|
| **Usuario crea nuevo canal web en HelpdeskLivechat** | Se crea en `helpdesk_channel_webs`. Engagement no lo ve. El SDK rechaza el `website_token` con 401. | **🔴 Crítico** |
| **Usuario actualiza token en HelpdeskLivechat** | Engagement sigue con el token viejo. SDK falla. | **🔴 Crítico** |
| **Usuario desactiva inbox en HelpdeskLivechat** | Engagement no se entera. El SDK sigue aceptando requests. | **🟡 Medio** |
| **HelpdeskLivechat se desinstala** | Los canales en `helpdesk_channel_webs` siguen existiendo. Engagement funciona, pero nadie puede administrarlos. | **🟡 Medio** |
| **Inbox carga relación polimórfica `$inbox->channel`** | Sigue funcionando porque `Inbox::CHANNEL_TYPE_MAP` apunta a `HelpdeskLivechat\Web`. Los inboxes no se ven afectados por el cambio en Engagement. | **🟢 Bajo** |
| **Tests de Engagement** | Todos pasan si se crean factories para `WebsiteChannel`. | **🟢 Bajo** |

**Conclusión Opción A:** Riesgo **MUY ALTO** de desincronización. Engagement y HelpdeskLivechat gestionarían canales web en tablas diferentes. Cualquier cambio en uno no se refleja en el otro.

---

### Opción B: Compartir tabla, cambiar modelo

**Implementación:**
1. Engagement crea un modelo `WebsiteChannel` que apunta a la tabla `helpdesk_channel_webs` existente
2. No se duplican datos
3. `EnsureWebsiteToken` usa el nuevo modelo

**¿Qué pasa si...?**

| Escenario | Impacto | Severidad |
|---|---|---|
| **Usuario crea canal web en HelpdeskLivechat** | Engagement lo ve porque comparten tabla. | **🟢 Ninguno** |
| **HelpdeskLivechat modifica el schema de `helpdesk_channel_webs`** | El modelo de Engagement rompe si no tiene el nuevo campo en `$fillable` o `casts()`. | **🟡 Medio** |
| **HelpdeskLivechat añade relaciones o lógica en `Web`** | Engagement no tiene acceso a esos métodos. | **🟡 Medio** |
| **HelpdeskLivechat se desinstala** | La tabla sigue existiendo. Engagement funciona. | **🟢 Bajo** |

**Conclusión Opción B:** Riesgo **MEDIO**. Compartir tabla evita desincronización, pero crea coupling indirecto al schema.

---

### Opción C: Abstraer con interfaz + repository ( refactor arquitectónico )

**Implementación:**
1. Crear interfaz `WebsiteChannelInterface` en un módulo compartido (ej: `Helpdesk` o `Contracts`)
2. `HelpdeskLivechat\Web` implementa la interfaz
3. Engagement depende de la interfaz, no del modelo concreto
4. Usar inyección de dependencias para resolver la implementación

**¿Qué pasa si...?**

| Escenario | Impacto | Severidad |
|---|---|---|
| **HelpdeskLivechat se desinstala** | Engagement necesita otra implementación de `WebsiteChannelInterface` o deja de funcionar. | **🟡 Medio** |
| **Se crea otro módulo de chat** | Puede implementar la misma interfaz. Engagement funciona con cualquiera. | **🟢 Bajo (es el objetivo)** |
| **Cambio de schema** | Solo afecta a la implementación, no a Engagement. | **🟢 Bajo** |

**Conclusión Opción C:** Riesgo **BAJO**, pero requiere refactorizar `Helpdesk` (módulo core) para añadir la interfaz. Es la solución más limpia arquitectónicamente.

---

### Opción D: Dejar la dependencia, renombrar atributos (mínima intrusión)

**Implementación:**
1. No se crea modelo nuevo
2. Se cambian los atributos del request de `livechat_channel` / `livechat_inbox` a `website_channel` / `website_inbox`
3. Se documenta la dependencia

**¿Qué pasa si...?**

| Escenario | Impacto | Severidad |
|---|---|---|
| **HelpdeskLivechat se desinstala** | Engagement rompe porque no encuentra la clase `Web`. | **🔴 Crítico** |
| **Todo lo demás** | Funciona igual que ahora. | **🟢 Ninguno** |

**Conclusión Opción D:** Riesgo **ALTO** si HelpdeskLivechat se va, pero cero riesgo en operación normal. Es la opción más pragmática a corto plazo.

---

## Recomendación

### Corto plazo (hoy): Opción D + preparación
- Renombrar atributos del request (`livechat_*` → `website_*`)
- Documentar la dependencia
- Crear interfaz `WebsiteChannelInterface` en `Helpdesk` (módulo core)

### Medio plazo (próximo sprint): Opción C
- Refactorizar `HelpdeskLivechat\Web` para implementar la interfaz
- Cambiar Engagement para depender de la interfaz
- Tests verifican que cualquier implementación funciona

### Largo plazo: Opción A solo si Engagement necesita campos que HelpdeskLivechat no tiene
- Ejemplo: Si Engagement necesita `tracking_domain`, `allowed_origins`, etc. que no existen en `Web`
- En ese caso, migrar datos y mantener sincronización via eventos/observers

---

## Plan de implementación seguro (Opción D + preparación C)

### Fase 1: Limpieza de atributos (sin riesgo)
- `EnsureWebsiteToken`: cambiar `livechat_channel` → `website_channel`, `livechat_inbox` → `website_inbox`
- Actualizar todos los controllers que leen `$request->attributes->get('livechat_...')`

### Fase 2: Interfaz en Helpdesk (riesgo bajo)
- Crear `Modules\Helpdesk\Contracts\WebsiteChannelContract` en `app/Contracts/`
- Definir métodos: `getWebsiteToken()`, `getInbox()`, `isActive()`

### Fase 3: Implementar interfaz (riesgo bajo)
- `HelpdeskLivechat\Web implements WebsiteChannelContract`
- Engagement depende de `WebsiteChannelContract`

### Fase 4: Fallback si HelpdeskLivechat no existe (riesgo medio)
- Si `Web::class` no existe, lanzar excepción clara: "HelpdeskLivechat required for web channels"
- O crear un `NullWebsiteChannel` que siempre rechaza requests

---

## Impacto en tests

Los 12 tests de Feature que usan `Web::class` necesitan:
- Usar factory de `Web` (sigue existiendo en HelpdeskLivechat)
- O crear un mock de `WebsiteChannelContract`

Ningún test necesita cambiar de modelo si usamos Opción D/C.

---

## Conclusión ejecutiva

**Desacoplar NO es urgente.** La dependencia actual funciona. El riesgo real aparece solo si:
1. HelpdeskLivechat se desinstala (poco probable)
2. Se necesitan campos de canal web que HelpdeskLivechat no tiene (posible a futuro)

**La acción recomendada hoy es:**
- Renombrar atributos del request (limpieza de código)
- Crear la interfaz en `Helpdesk` (preparación)
- **NO** duplicar tabla ni modelo (evita desincronización)

¿Quieres que implemente la Fase 1 (renombrar atributos + crear interfaz)? Es 2h de trabajo, cero riesgo de producción.
