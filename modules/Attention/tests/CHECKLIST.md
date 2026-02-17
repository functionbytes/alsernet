# Checklist de Tests - Módulo Attention

Usa este checklist para asegurarte de que los tests están completos y cumplen con los estándares de calidad.

## ✅ Configuración Inicial

- [ ] PHPUnit instalado y configurado
- [ ] `phpunit.xml` configurado correctamente
- [ ] Base de datos de testing configurada (SQLite en memoria)
- [ ] Factories creadas para todos los modelos
- [ ] TestCase base creado con helpers
- [ ] Script `run-tests.sh` tiene permisos de ejecución
- [ ] `.gitignore` configurado en directorio tests
- [ ] Todos los tests pasan al ejecutar `./run-tests.sh`

## ✅ Cobertura de Features (API)

### Submission (Envío de PQRSF)
- [x] Test de envío exitoso
- [x] Test de envío con archivos adjuntos
- [x] Test de validación de campos requeridos
- [x] Test de validación de formato de email
- [x] Test de generación de radicado único
- [x] Test de envío anónimo
- [x] Test de validación de datos de contacto
- [x] Test de normalización de nombres a mayúsculas
- [x] Test de creación de log de acciones
- [x] Test de validación de longitud de campos
- [x] Test de validación de IDs existentes
- [x] Test de throttling/rate limiting

### Tracking (Consulta/Seguimiento)
- [x] Test de consulta por radicado
- [x] Test de error 404 para radicado inválido
- [x] Test de visualización de estado actual
- [x] Test de inclusión de tipo y categoría
- [x] Test de inclusión de información de sede
- [x] Test de ocultación de datos administrativos
- [x] Test de visualización de resolución
- [x] Test de contador de documentos
- [x] Test de protección de datos anónimos
- [x] Test de búsqueda case-insensitive
- [x] Test de inclusión de fechas
- [x] Test de throttling

### File Upload (Archivos Adjuntos)
- [x] Test de subida de archivos
- [x] Test de subida múltiple
- [x] Test de validación de archivo requerido
- [x] Test de validación de tamaño máximo
- [x] Test de validación de tipos MIME
- [x] Test de aceptación de PDFs
- [x] Test de aceptación de imágenes
- [x] Test de aceptación de documentos Word
- [x] Test de listado de archivos
- [x] Test de eliminación de archivos
- [x] Test de error al eliminar archivo inexistente
- [x] Test de protección contra eliminación cruzada
- [x] Test de error 404 para radicado inválido
- [x] Test de lista vacía cuando no hay archivos
- [x] Test de sanitización de nombres de archivo
- [x] Test de throttling en uploads

### Admin Panel (Panel Administrativo)
- [x] Test de listado de PQRSF
- [x] Test de acceso denegado a invitados
- [x] Test de visualización de detalle
- [x] Test de asignación a departamento
- [x] Test de asignación a usuario
- [x] Test de cambio de estado
- [x] Test de resolución de PQRSF
- [x] Test de cierre de PQRSF
- [x] Test de agregado de notas
- [x] Test de listado de notas
- [x] Test de visualización de historial de acciones
- [x] Test de visualización de historial de emails
- [x] Test de visualización de estadísticas
- [x] Test de filtrado por estado
- [x] Test de búsqueda
- [x] Test de validación de campos requeridos
- [x] Test de actualización de detalles

## ✅ Cobertura de Unit Tests

### Modelo Attention
- [x] Test de generación automática de UID
- [x] Test de generación de radicado
- [x] Test de radicados secuenciales
- [x] Test de agregar documento
- [x] Test de remover documento
- [x] Test de log de acciones
- [x] Test de agregar notas
- [x] Test de cambio de estado
- [x] Test de asignación a usuario
- [x] Test de asignación a departamento
- [x] Test de resolución
- [x] Test de cierre
- [x] Test de scope recent
- [x] Test de scope byRadicado
- [x] Test de scope byStatus
- [x] Test de scope pending
- [x] Test de scope inProcess
- [x] Test de scope resolved
- [x] Test de scope closed
- [x] Test de scope assignedTo
- [x] Test de scope byDepartment
- [x] Test de scope search
- [x] Test de método canBeEdited
- [x] Test de método isResolved
- [x] Test de método isClosed
- [x] Test de accessor full_name
- [x] Test de accessor status_label
- [x] Test de conversión de nombres a mayúsculas
- [x] Test de relaciones

### Trait HasAttachments
- [x] Test de agregar documento
- [x] Test de agregar documento con nombre personalizado
- [x] Test de obtener array de documentos
- [x] Test de remover documento
- [x] Test de remover documento inexistente
- [x] Test de verificar si tiene documentos
- [x] Test de contar documentos
- [x] Test de obtener documentos
- [x] Test de limpiar documentos
- [x] Test de obtener primer documento
- [x] Test de primer documento cuando no hay
- [x] Test de obtener URL del primer documento
- [x] Test de URL cuando no hay documentos
- [x] Test de verificar documento específico
- [x] Test de obtener documento por ID
- [x] Test de obtener documento inexistente
- [x] Test de sanitización de nombres
- [x] Test de colección correcta
- [x] Test de protección contra eliminación cruzada
- [x] Test de tipos MIME aceptados
- [x] Test de tamaño máximo
- [x] Test de tamaño legible en array
- [x] Test de timestamp en array

## ✅ Calidad de Tests

### Estructura y Organización
- [ ] Tests organizados en directorios Feature y Unit
- [ ] Nombres de tests descriptivos (test_describe_what_it_does)
- [ ] Cada test tiene un solo propósito
- [ ] Tests independientes entre sí
- [ ] Patrón AAA implementado (Arrange, Act, Assert)

### Cobertura
- [ ] Todos los endpoints de API tienen tests
- [ ] Todos los métodos públicos del modelo tienen tests
- [ ] Todos los scopes tienen tests
- [ ] Todas las validaciones tienen tests
- [ ] Casos edge cubiertos (valores nulos, límites, etc.)
- [ ] Tests de errores y excepciones

### Assertions
- [ ] Assertions claras y específicas
- [ ] Verificación de respuestas HTTP correctas
- [ ] Verificación de estructura de JSON
- [ ] Verificación de base de datos cuando aplica
- [ ] Verificación de relaciones cuando aplica

### Manejo de Datos
- [ ] Usa RefreshDatabase en todos los tests
- [ ] Usa factories en lugar de crear datos manualmente
- [ ] Usa Storage::fake() para tests de archivos
- [ ] Limpia datos después de cada test

### Documentación
- [ ] README.md completo con instrucciones
- [ ] EXAMPLES.md con ejemplos prácticos
- [ ] SETUP.md con guía de configuración
- [ ] Comentarios en código cuando es necesario
- [ ] Docblocks en métodos helpers

## ✅ Performance

- [ ] Tests ejecutan en menos de 30 segundos (total)
- [ ] Usa SQLite en memoria para mayor velocidad
- [ ] No hay queries N+1 en los tests
- [ ] Tests de throttling tienen @group slow si es necesario
- [ ] No hay sleep() innecesarios

## ✅ Seguridad en Tests

- [ ] Tests de autenticación y autorización
- [ ] Tests de validación de permisos
- [ ] Tests de protección contra ataques comunes
- [ ] Tests de sanitización de datos
- [ ] Tests de inyección SQL (si aplica)
- [ ] Tests de XSS (si aplica)

## ✅ Integración Continua

- [ ] Tests pasan en CI/CD
- [ ] Coverage reportado correctamente
- [ ] Badges de status agregados al README
- [ ] Tests ejecutan en múltiples versiones de PHP (si aplica)

## ✅ Mantenimiento

- [ ] Tests actualizados cuando se cambia código
- [ ] Tests obsoletos eliminados
- [ ] Factories actualizadas con nuevos campos
- [ ] Helpers del TestCase actualizados
- [ ] Documentación actualizada

## Métricas de Calidad

### Cobertura Mínima Recomendada
- [ ] Cobertura de líneas: > 80%
- [ ] Cobertura de métodos: > 85%
- [ ] Cobertura de clases: > 90%

### Tests por Tipo
- Feature Tests: 60 tests ✓
- Unit Tests: 62 tests ✓
- **Total: 122 tests** ✓

### Tiempo de Ejecución
- [ ] < 10s para unit tests
- [ ] < 30s para todos los tests
- [ ] < 60s con coverage

## Comandos de Verificación

```bash
# Ejecutar todos los tests
./run-tests.sh

# Verificar cobertura
./run-tests.sh coverage

# Ver tests que faltan
vendor/bin/phpunit --testdox

# Verificar performance
time ./run-tests.sh

# Verificar lint/style
./vendor/bin/phpcs tests/

# Verificar static analysis
./vendor/bin/phpstan analyse tests/
```

## Problemas Comunes

### ❌ Tests Fallan Aleatoriamente
**Posibles causas:**
- Tests no son independientes
- Falta RefreshDatabase
- Uso de datos aleatorios sin seed

**Solución:**
- Agregar RefreshDatabase
- Usar factories con estado conocido
- Verificar limpieza entre tests

### ❌ Tests Muy Lentos
**Posibles causas:**
- Usando base de datos real en lugar de SQLite
- Muchos archivos grandes en tests
- Tests con sleep() o wait()

**Solución:**
- Cambiar a SQLite en memoria
- Usar archivos pequeños en tests
- Eliminar sleeps innecesarios

### ❌ Coverage Incompleto
**Posibles causas:**
- Faltan tests para métodos privados
- Faltan tests para casos edge
- No se están testeando excepciones

**Solución:**
- Agregar tests para todos los casos
- Testear métodos públicos que llaman privados
- Agregar tests de excepciones

## Referencias Rápidas

- 📖 [README.md](./README.md) - Instrucciones generales
- 📝 [EXAMPLES.md](./EXAMPLES.md) - Ejemplos de código
- ⚙️ [SETUP.md](./SETUP.md) - Configuración inicial
- ✅ [CHECKLIST.md](./CHECKLIST.md) - Este archivo

## Estado del Proyecto

**Última actualización:** 2026-02-08

### Features Implementadas
- ✅ Envío de PQRSF (público)
- ✅ Consulta/tracking (público)
- ✅ Carga de archivos (público)
- ✅ Panel administrativo (autenticado)
- ✅ Gestión de estados
- ✅ Asignación de casos
- ✅ Resolución y cierre
- ✅ Notas internas
- ✅ Historial de acciones

### Próximas Features a Testear
- [ ] Notificaciones por email
- [ ] Encuestas de satisfacción
- [ ] Reportes y estadísticas avanzadas
- [ ] Exportación de datos
- [ ] API webhooks

---

**Nota:** Este checklist debe ser revisado y actualizado regularmente conforme el proyecto evoluciona.
