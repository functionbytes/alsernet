# Auditoría — HelpdeskDocument

> Fecha: 2026-06-29 · Health score: 63/100 · Estado: needs-work

**Resumen:** Módulo puente que incrusta el expediente del módulo Document dentro del inbox de Helpdesk. Sus 3 endpoints propios están bien escritos y protegidos por permiso + ownership (cliente/email), pero deja la mayoría de las acciones mutadoras de documentos cableadas a rutas `api.documents.*` débilmente protegidas (solo `auth:web`), entrega una subida desde dispositivo a medio cablear, arrastra un N+1 en el listado, blades demo huérfanos y cero tests. La migración de autorización quedó a medias: solo se proxearon file-delete, panel-view e import-from-chat por rutas helpdesk-scoped; el resto (upload, approve, reject, send-*, notes, attachments) sigue alcanzable por cualquier usuario web autenticado contra cualquier documento por UID.

## Tabla de hallazgos

| ID | Sev | Categoría | Archivo:línea | Estado verif. | Esfuerzo | Título |
|----|-----|-----------|---------------|---------------|----------|--------|
| HD-DOC-01 | high | security | resources/views/inbox-slots/_document-detail.blade.php:209-225 | [CONFIRMADO] | L | Acciones mutadoras de documentos saltan el permiso helpdesk + ownership del módulo |
| HD-DOC-02 | medium | security | resources/views/modals/doc-from-chat.blade.php:164-168 | [DUDOSO] | S | DOM XSS por nombre/URL sin escapar en galería de chat |
| HD-DOC-03 | medium | wiring | resources/views/modals/doc-from-chat.blade.php:221-254 | [DUDOSO] | M | Subida desde dispositivo apunta a endpoint que no acepta ficheros |
| HD-DOC-04 | medium | performance | app/Services/ConversationDocumentLinker.php:77-79 | [DUDOSO] | S | N+1 sobre media al construir el listado del expediente |
| HD-DOC-05 | medium | conventions | app/Http/Controllers/Managers/ChatGalleryDocumentController.php:34-41 | [DUDOSO] | S | Validación inline en vez de Form Request |
| HD-DOC-06 | medium | tests | composer.json:11 | [DUDOSO] | M | Sin tests automatizados en el módulo |
| HD-DOC-07 | low | quality | resources/views/modals/preview.blade.php:41-146 | [DUDOSO] | S | Blades demo/preview huérfanos en producción |
| HD-DOC-08 | low | performance | app/Services/ConversationDocumentLinker.php:77-91 | [DUDOSO] | M | Carga y ordenación en PHP de todos los documentos del cliente sin límite |
| HD-DOC-09 | low | security | app/Http/Controllers/Managers/ChatGalleryDocumentController.php:143-145 | [DUDOSO] | M | SSRF potencial vía addMediaFromUrl fallback |
| HD-DOC-10 | low | quality | app/Http/Controllers/Managers/DocumentFileController.php:46-49 | [DUDOSO] | M | Borrar fichero resetea incondicionalmente el estado a awaiting_documents |
| HD-DOC-11 | low | quality | resources/views/inbox-slots/_document-detail.blade.php:41-46 | [DUDOSO] | S | Query a BD ejecutada dentro de la vista Blade |
| HD-DOC-12 | low | ux | resources/views/modals/doc-from-chat.blade.php:211-213 | [DUDOSO] | S | Etiqueta del botón de importar pierde contexto tras éxito |
| HD-DOC-13 | low | conventions | composer.json:8-12 | [DUDOSO] | S | Entradas PSR-4 colgantes para directorios inexistentes |
| HD-DOC-14 | low | ux | resources/views/inbox-slots/right-panel-document-tab.blade.php:36,105,112 | [DUDOSO] | S | Estilos inline en Blade (contra regla del proyecto) |

## Hallazgos detallados

### HIGH

#### HD-DOC-01 · [CONFIRMADO] · Acciones mutadoras de documentos saltan el permiso helpdesk + ownership del módulo
- **Archivo:** `modules/HelpdeskDocument/resources/views/inbox-slots/_document-detail.blade.php:209-225`
- **Evidencia:** El panel de detalle expone `data-url-upload/approve/reject/send-*/notes/upload-attach/update` apuntando a rutas `api.documents.*`. Esas rutas (`modules/Document/routes/api.php:27-100`) están protegidas únicamente por `['web','auth:web']` — sin permiso `helpdesk.conversations.*` y sin el scoping cliente/email que el módulo sí añadió para el file-delete (`DocumentFileController`). El equipo migró solo el endpoint file-DELETE (comentario de ruta: «replaces the public api.documents.files.delete»), dejando ~15 acciones mutadoras más alcanzables por cualquier usuario web autenticado contra cualquier UID de documento.

  Verificación a nivel de código en `DocumentValidationController`:
  - `assignUser` (línea 79): autorización cero — cualquier usuario `auth:web` puede reasignar cualquier documento por UID.
  - `uploadDocument` (línea 752): autorización cero — subida de ficheros a cualquier documento por UID.
  - `sendApproval`, `sendRejection`, `sendReminder`, `sendNotification`, `sendUploadConfirmation`, `sendMissingDocuments`: los comentarios fuente dicen explícitamente «Sin verificación de autorización».
  - `emailHistory`, `emailPreview`, `getActionHistory`, `getEmailHistory`, `getStatusTimeline`: todas las llamadas `$this->authorize()` están comentadas.
  - `uploadAdditionalAttachment` (línea 962): autorización cero.

  Mitigación parcial solo en dos métodos: `approveStage`/`rejectStage` hacen un check por rol del módulo Document (`getUserProfile()` con `hasRole`) y `addNote`/`updateNote`/`deleteNote` usan `canDocument()` — pero ninguno usa permisos helpdesk ni scope por ownership de la conversación.
- **Impacto:** Un usuario autenticado sin permiso helpdesk, o un agente sin relación con el cliente de la conversación, puede subir/aprobar/rechazar/borrar adjuntos y enviar correos al cliente de documentos arbitrarios.
- **Recomendación:** Replicar el patrón de `DocumentFileController`: proxear estas acciones por rutas propiedad de helpdesk que verifiquen `can:helpdesk.conversations.*` y el ownership por email conversación↔documento; o añadir middleware `can:` + checks de ownership en los controladores de Document.
- **Esfuerzo:** L

## Plan de ataque priorizado

1. **HD-DOC-01 (high, L):** Cerrar la migración parcial de autorización. Enrutar las acciones mutadoras restantes por endpoints helpdesk-scoped con verificación de ownership, en lugar de las rutas `api.documents.*` solo-`auth:web`. Es el riesgo de seguridad dominante del módulo.
2. **HD-DOC-03 (medium, M):** Arreglar o eliminar la subida desde dispositivo «Subir desde mi equipo» que postea `files[]` a un endpoint que solo acepta `file_ids`.
3. **HD-DOC-06 (medium, M):** Añadir cobertura `tests/` para los guards de ownership y la lógica de importación (actualmente cero).
4. **HD-DOC-02 (medium, S):** Escapar `m.name`/`m.url` en el render de la galería de `doc-from-chat.blade.php`.
5. **HD-DOC-04 / HD-DOC-05 (medium, S):** Eager-load de `media` + extraer Form Request.
6. Resto de low (HD-DOC-07 a HD-DOC-14): limpieza de calidad/UX/convenciones.

## Quick wins

- **HD-DOC-04:** Añadir `'media'` al `with()` de `ConversationDocumentLinker::documentsForConversation` para matar el N+1 de `getMedia()` por documento en el presenter del listado.
- **HD-DOC-02:** Escapar `m.name`/`m.url` en el render de la galería de `doc-from-chat.blade.php`.
- **HD-DOC-07:** Borrar los blades demo huérfanos (`preview`/`doc-view`/`doc-manage`/`docs-viewer`) o sacarlos del módulo de producción.
- **HD-DOC-13:** Quitar las entradas PSR-4 colgantes (`Factories`/`Seeders`/`Tests`) de `composer.json` o crear los directorios.
- **HD-DOC-14:** Mover estilos inline a clases utilitarias/CSS (`d-none`, clases `docs-*` existentes).
- **HD-DOC-12:** Re-ejecutar `updateImportBtn(selectedCount)` en `.always()` en vez de fijar la etiqueta a mano.

## Fortalezas

- `DocumentPanelController` y `DocumentFileController` aplican tanto el permiso Spatie (`can:helpdesk.conversations.view`) COMO un guard de ownership por cliente/email (`abort_if` ante mismatch de email) antes de servir/borrar expedientes.
- La mayoría del JS escapa datos de usuario correctamente con el idiom `$('<s>').text(x).html()` antes de insertar en el DOM (notas, timeline, adjuntos).
- `importFromChat` restringe las URLs importadas a la propia lista de adjuntos de la conversación (`array_intersect`) y prefiere una ruta local del disco público antes que descargar URLs remotas.
- Las rutas siguen la excepción documentada de naming de Helpdesk (`manager.helpdesk.conversations.*`) con throttle en el endpoint de escritura y middleware de grupo `web+auth`.
- Los servicios tienen return types claros, PHPDoc y usan parámetros bound en `whereRaw` (sin inyección SQL).

## Cobertura de la auditoría

Solo análisis estático (suite de BD/tests no ejecutada intencionalmente). Se revisaron por completo los 7 ficheros PHP (3 controladores, 2 servicios, provider, rutas) y los 8 blades; se cruzó con el slot del panel derecho de Helpdesk que los consume y con el `api.php` del módulo Document para validar el cableado de rutas y el middleware. No existen models/migraciones/policies/jobs/form-requests/seeders/tests en el módulo (es un puente), lo cual es arquitectónicamente esperado salvo por los tests ausentes.

Verificación de hallazgos: se revisó 1 hallazgo a fondo. **HD-DOC-01 queda totalmente confirmado en severidad high.** Las rutas API del módulo Document consumidas por el blade del inbox de HelpdeskDocument están protegidas solo por auth de sesión, con llamadas de autorización ausentes o explícitamente comentadas. La migración incompleta — solo file-delete, panel-view e import-from-chat se proxearon por rutas helpdesk-scoped — deja upload, approve, reject, send-*, notes y operaciones de adjuntos alcanzables por cualquier usuario web autenticado contra cualquier UID de documento sin permiso helpdesk ni verificación de ownership por email del cliente.

Los hallazgos HD-DOC-02 a HD-DOC-14 quedan marcados [DUDOSO] (no re-verificados a nivel de código en esta pasada); mantienen su severidad original derivada del análisis estático.

## Descartados en verificación

Ninguno. Ningún hallazgo fue refutado en la verificación.
