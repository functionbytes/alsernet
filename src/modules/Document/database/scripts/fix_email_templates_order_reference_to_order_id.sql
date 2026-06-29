-- =============================================================================
-- Fix: Reemplaza {ORDER_REFERENCE} por {ORDER_ID} en plantillas de email del
-- modulo Document (subject + content) en todas las traducciones (lang_id).
--
-- Tablas:
--   mailer_templates       (filtro: module = 'documents', key IN (...))
--   mailer_template_langs  (subject, content)
--
-- Plantillas afectadas (keys): document_initial_request, document_missing_documents,
-- document_reminder, document_approved, document_rejected, document_completed.
--
-- Recomendado: ejecutar dentro de transaccion. Hacer backup previo.
-- =============================================================================

START TRANSACTION;

-- Backup ligero opcional (descomentar si se desea):
-- CREATE TABLE IF NOT EXISTS mailer_template_langs_backup_2026_05_07 AS
--   SELECT mtl.* FROM mailer_template_langs mtl
--   INNER JOIN mailer_templates mt ON mt.id = mtl.mailer_template_id
--   WHERE mt.module = 'documents'
--     AND mt.key IN (
--       'document_initial_request','document_missing_documents','document_reminder',
--       'document_approved','document_rejected','document_completed'
--     );

-- Vista previa (filas que se actualizaran)
SELECT mtl.id, mt.key, mtl.lang_id, mtl.subject
FROM mailer_template_langs mtl
INNER JOIN mailer_templates mt ON mt.id = mtl.mailer_template_id
WHERE mt.module = 'documents'
  AND mt.key IN (
    'document_initial_request','document_missing_documents','document_reminder',
    'document_approved','document_rejected','document_completed'
  )
  AND (mtl.subject LIKE '%{ORDER_REFERENCE}%' OR mtl.content LIKE '%{ORDER_REFERENCE}%');

-- Actualizar SUBJECT
UPDATE mailer_template_langs mtl
INNER JOIN mailer_templates mt ON mt.id = mtl.mailer_template_id
SET mtl.subject = REPLACE(mtl.subject, '{ORDER_REFERENCE}', '{ORDER_ID}'),
    mtl.updated_at = NOW()
WHERE mt.module = 'documents'
  AND mt.key IN (
    'document_initial_request','document_missing_documents','document_reminder',
    'document_approved','document_rejected','document_completed'
  )
  AND mtl.subject LIKE '%{ORDER_REFERENCE}%';

-- Actualizar CONTENT
UPDATE mailer_template_langs mtl
INNER JOIN mailer_templates mt ON mt.id = mtl.mailer_template_id
SET mtl.content = REPLACE(mtl.content, '{ORDER_REFERENCE}', '{ORDER_ID}'),
    mtl.updated_at = NOW()
WHERE mt.module = 'documents'
  AND mt.key IN (
    'document_initial_request','document_missing_documents','document_reminder',
    'document_approved','document_rejected','document_completed'
  )
  AND mtl.content LIKE '%{ORDER_REFERENCE}%';

-- Verificacion: no debe quedar ninguna fila con {ORDER_REFERENCE}
SELECT mtl.id, mt.key, mtl.lang_id,
       (mtl.subject LIKE '%{ORDER_REFERENCE}%') AS subject_has_ref,
       (mtl.content LIKE '%{ORDER_REFERENCE}%') AS content_has_ref
FROM mailer_template_langs mtl
INNER JOIN mailer_templates mt ON mt.id = mtl.mailer_template_id
WHERE mt.module = 'documents'
  AND mt.key IN (
    'document_initial_request','document_missing_documents','document_reminder',
    'document_approved','document_rejected','document_completed'
  )
  AND (mtl.subject LIKE '%{ORDER_REFERENCE}%' OR mtl.content LIKE '%{ORDER_REFERENCE}%');

-- Si la verificacion devuelve 0 filas, confirmar:
COMMIT;
-- En caso contrario:
-- ROLLBACK;
