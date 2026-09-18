# Sistema de menciones (@)

Guía técnica del flujo end-to-end de menciones en el inbox v4 del módulo Helpdesk.

## Resumen rápido

Cuando un agente escribe un mensaje (interno o externo) en una conversación, el body se parsea en busca de `@handles`. Cada handle resuelto a un `User` real recibe una `MentionNotification` (database + broadcast, opcionalmente email). Los `@handles` se renderizan como chips clickables en el thread y se "limpian" (sustituyen por nombre legible) en envíos externos para que el cliente final no vea identificadores internos.

---

## 1. Sintaxis de los handles

| Sintaxis | Resuelve a | Ejemplo |
|---|---|---|
| `@firstname.lastname` | Agente individual (`User`) | `@carmen.lopez` |
| `@team_key` | Equipo (`Group.key`) | `@general_support` |
| `@all` | Todos los usuarios del workspace | `@all` |
| `@here` | Agentes online (sesión < 5 min) | `@here` |
| `@team` | Miembros del equipo asignado a la conversación | `@team` |

**Reglas de la regex** (`MentionParser::PATTERN`):

```
/(?:^|[\s])@([\p{L}0-9._-]+)/u
```

- Debe estar precedido por **inicio de string** o **whitespace**. Esto evita capturar emails (`user@example.com`).
- Acepta letras Unicode (incluye acentos), dígitos, punto, guión, guión bajo.
- Case-insensitive: el handle se normaliza a lowercase.
- Dedup: si un handle aparece varias veces solo se procesa una.

---

## 2. Resolución (handle → User)

`MentionParser::resolve($body, $excludeUserId, ?Conversation $conv)` devuelve:

```php
[
    'users'    => Collection<User>,   // todos los usuarios a notificar (deduplicados)
    'teams'    => Collection<Group>,  // equipos mencionados directamente
    'handles'  => array<string>,      // handles tal cual aparecieron
    'special'  => array<string>,      // handles especiales detectados (all/here/team)
]
```

Orden de resolución de cada handle:

1. **Especiales** (`all`, `here`, `team`) → expansión inline.
2. **Equipo** si `Group::key === handle`.
3. **Agente** si `LOWER(REPLACE(CONCAT(firstname,'.',lastname), ' ', '.')) === handle`.

`@team` requiere que la conversación tenga `group_id` set; si no, no expande.

---

## 3. Flujo del envío de mensaje

```
ConversationsController::storeMessage()
  └─ ConversationMessageService::store()
       ├─ MentionParser::resolve()  ← detecta menciones
       ├─ guarda metadata.mentions = { handles, user_ids, team_ids }
       ├─ crea ConversationItem
       ├─ si NO is_internal:
       │    └─ MentionParser::stripForExternal()  ← reemplaza @handles por nombres
       │         └─ OutboundMessageService::sendReply()
       ├─ por cada user mencionado:
       │    └─ event(new MentionDetected($conv, $item, $user, $authorName))
       └─ event(new ConversationItemCreated($item))

EventServiceProvider:
  MentionDetected → SendMentionNotification (queued)

SendMentionNotification:
  └─ $user->notify(new MentionNotification(...))

MentionNotification::via():
  - 'database'  ← siempre
  - 'broadcast' ← siempre
  - 'mail'      ← si user.agentSettings.preferences.email_on_mention === true
```

---

## 4. Persistencia (audit trail)

Cada `ConversationItem` con menciones almacena en su columna JSON `metadata`:

```json
{
  "mentions": {
    "handles": ["carmen.lopez", "general_support", "all"],
    "user_ids": [12, 8, 4, 15],
    "team_ids": [1]
  }
}
```

Útil para reportes posteriores ("¿cuántas veces fue mencionado X?") sin re-parsear bodies.

---

## 5. Render visual (frontend)

### Server-side (Blade)

`ConversationItem::getBodyHtmlAttribute()`:

```php
$item->body_html
// "Hola <span class=\"bv-mention-chip\" data-bv-mention-handle=\"carmen.lopez\">@carmen.lopez</span>"
```

Usado en `thread.blade.php:170` con `{!! $item->body_html !!}` (escape ya aplicado).

### Client-side (JS optimistic)

`appendBubbleToThread()` en `inbox-v4.js` aplica la misma regex sobre `escape(item.body)` para mantener consistencia visual entre el bubble que aparece justo tras enviar (frontend) y el que vuelve renderizado del server.

### Click en chip

Un click en `.bv-mention-chip` abre un popover (`#bv-mention-popover`) con:

- Avatar (color por id si agente, gradiente oscuro si equipo, gradiente índigo si especial)
- Nombre + role badge si aplica
- Status / miembros / descripción
- Datos cacheados de `/panel/helpdesk/api/agents-autocomplete`

---

## 6. Strip para canales externos

Cuando el mensaje **no es interno** y se envía a WhatsApp / Email / etc., `MentionParser::stripForExternal()` reemplaza:

| Handle | Sustitución |
|---|---|
| `@carmen.lopez` | `Carmen López` |
| `@general_support` | `Soporte General` |
| `@all`, `@here`, `@team` | sin tocar (texto literal queda) |

Esto evita que el cliente final vea identificadores internos del workspace en el mensaje recibido.

---

## 7. Integración UI

### Composer

| Componente | Trigger |
|---|---|
| Modal `data-bv-modal="mention"` | Click en `#bv-btn-mention` (botón `@`) |
| Dropdown inline | Tipear `@` en el textarea del composer |
| Tabs `Agentes` / `Equipos` | Sólo en el modal |
| Búsqueda live | Debounce 180 ms (modal) / 120 ms (dropdown) |

### Endpoint

`GET /panel/helpdesk/api/agents-autocomplete?q=...&include_self=0`

Devuelve:

```json
{
  "agents": [
    {
      "id": 2, "name": "Carmen López", "username": "carmen.lopez", "email": "...",
      "initials": "CL", "online": true, "status": "online", "status_label": "En línea",
      "role": "Supervisor", "skills": ["SAP","Redes"],
      "workload_current": 8, "workload_max": 15
    }
  ],
  "teams": [
    {
      "id": 1, "name": "Soporte General", "key": "general_support",
      "description": "...", "members_count": 3,
      "workload_current": 1, "workload_max": 15
    }
  ]
}
```

Auth: solo `auth` middleware (sin role) — cualquier agente autenticado puede usar el autocomplete.
Throttle: 120/min.

---

## 8. Preferencias del usuario

Para activar el canal email cuando es mencionado:

```php
$user->agentSettings->preferences = array_merge($user->agentSettings->preferences ?? [], [
    'email_on_mention' => true,
]);
$user->agentSettings->save();
```

Default: `false` (solo database + broadcast).

---

## 9. Casos edge cubiertos

- Email en body (`user@example.com`) → **no** se captura.
- `@a` con un solo carácter → captura (cualquier `\p{L}0-9._-` sirve).
- Mismo handle dos veces → 1 notificación.
- Autor mencionándose a sí mismo → excluido.
- `@team` en conversación sin group → 0 expansión, no falla.
- Mensajes antiguos (sin `metadata.mentions`) → render visual sigue funcionando porque `getBodyHtmlAttribute()` re-parsea por regex.
- HTML/XSS → todo body pasa por `e()` antes del regex; el chip nunca contiene HTML del usuario.

---

## 10. Archivos involucrados

| Archivo | Rol |
|---|---|
| `app/Services/MentionParser.php` | Extracción + resolución + strip |
| `app/Services/ConversationMessageService.php` | Hook en `store()` |
| `app/Models/ConversationItem.php` | Accessor `body_html` |
| `app/Notifications/MentionNotification.php` | Mensaje + canales (database/broadcast/mail) |
| `app/Events/MentionDetected.php` | Evento dispatcheado |
| `app/Listeners/SendMentionNotification.php` | Ejecuta el `notify()` |
| `app/Http/Controllers/Managers/AgentsController.php` | Endpoint `search()` para el picker |
| `resources/views/managers/inbox/partials/modals/mention.blade.php` | Modal con tabs |
| `resources/views/managers/inbox/partials/thread.blade.php` | Render del bubble |
| `public/vendor/helpdesk/inbox-v4.js` | Picker dropdown + modal + popover |
| `resources/css/inbox-v4.css` | Estilos `.bv-mention-chip`, `.bv-mention-popover`, modal |

---

## 11. Tests sugeridos

```php
// tests/Feature/MentionsTest.php
test('parses simple agent mention', function () {
    $parser = app(MentionParser::class);
    expect($parser->extractHandles('Hola @carmen.lopez'))->toBe(['carmen.lopez']);
});

test('ignores email patterns', function () {
    $parser = app(MentionParser::class);
    expect($parser->extractHandles('mi email user@example.com'))->toBe([]);
});

test('expands @team to group members', function () {
    $group = Group::factory()->create();
    $member = User::factory()->create();
    $group->users()->attach($member);
    $conv = Conversation::factory()->create(['group_id' => $group->id]);

    $resolved = app(MentionParser::class)->resolve('@team revisad', null, $conv);

    expect($resolved['users']->pluck('id')->all())->toContain($member->id);
});

test('store dispatches MentionDetected event for each user', function () {
    Event::fake();
    $conv = Conversation::factory()->create();
    $mentioned = User::factory()->create(['firstname' => 'Carmen', 'lastname' => 'Lopez']);

    app(ConversationMessageService::class)->store($conv, [
        'body' => 'Hola @carmen.lopez',
        'is_internal' => true,
    ]);

    Event::assertDispatched(MentionDetected::class, fn ($e) => $e->mentionedUser->id === $mentioned->id);
});
```
