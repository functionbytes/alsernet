# Frontend Documentation - Alsernet

Esta carpeta contiene toda la documentación relacionada con el frontend de Alsernet, incluyendo reglas de diseño, patrones de componentes y guías de implementación.

## Archivos de Documentación

### 📋 design-rules.md
**Reglas de diseño obligatorias para el agente frontend**

Contiene:
- ✅ Reglas de componentes (Select2, inputs, validación)
- ✅ Estándares de Bootstrap
- ✅ Clases CSS obligatorias
- ✅ Patrones de validación
- ✅ Uso de iconos
- ✅ Ejemplos del codebase

**LEER PRIMERO**: El agente frontend debe revisar `design-rules.md` antes de crear o modificar cualquier componente.

## Guía Rápida para el Agente Frontend

Cuando crees o modifiques una vista:

### 🔴 REGLAS OBLIGATORIAS (Sin excepciones)

1. **Responsive Design en Formularios**
   - SIEMPRE usar: `col-12 col-md-6` (no solo `col-6`)
   - Estructura: `col-{mobile} col-md-{tablet} col-lg-{desktop}`
   - Mobile first: empezar con `col-12`
   - Ver: `design-rules.md` → Sección 2
   - Ejemplo:
     ```blade
     <div class="col-12 col-md-6">  <!-- ✅ CORRECTO -->
     <div class="col-6">             <!-- ❌ INCORRECTO -->
     ```

2. **Selects (Form Controls)**
   - Siempre agregar clase `select2`
   - Inicializar con Select2 en JavaScript
   - Ver: `design-rules.md` → Sección 1

### 📋 ESTÁNDARES (Mantener Consistencia)

3. **Formularios con Validación**
   - Usar jQuery Validate
   - Seguir patrón de highlight/unhighlight
   - Mostrar errores con `field-validation-error`
   - Ver: `design-rules.md` → Sección 4

4. **Bootstrap Classes**
   - Usar clases estándar de Bootstrap 5.3
   - Mantener consistencia con otros componentes
   - Ver: `design-rules.md` → Sección 2

5. **Icons**
   - Usar Tabler Icons solamente
   - Prefijos: `ti ti-[nombre]`
   - Ver: `design-rules.md` → Sección 5

## Estructura de Directorios

```
docs/frontend/
├── README.md              ← Estás aquí
└── design-rules.md        ← Reglas obligatorias
```

## Referencias Útiles

### Recursos Externos
- [Bootstrap 5.3 Documentation](https://getbootstrap.com/docs/5.3/)
- [Select2 Documentation](https://select2.org/)
- [jQuery Validate Documentation](https://jqueryvalidation.org/)
- [Tabler Icons](https://tabler-icons.io/)

### En el Codebase
- Layout principal: `resources/views/layouts/managers.blade.php`
- Componentes reutilizables: `resources/views/managers/components/`
- Formularios ejemplo: `resources/views/managers/views/settings/`

## Recordatorios Importantes

⚠️ **SIEMPRE que crees un `<select>`:**
```blade
<!-- ✅ CORRECTO -->
<select class="form-select select2" ...>

<!-- ❌ INCORRECTO -->
<select class="form-select" ...>
```

⚠️ **Para validación con Select2:**
```javascript
// Inicializar Select2
$('.select2').select2({...});

// Validar al cambiar
$('.select2').on('change', function() {
    $(this).valid();
});
```

## Preguntas Frecuentes

**P: ¿Qué es Select2?**
A: Es una librería que mejora los select HTML nativos con búsqueda, estilos personalizados y mejor experiencia de usuario.

**P: ¿Por qué es obligatoria la clase select2?**
A: Asegura consistencia visual, búsqueda funcional, y validación confiable en toda la aplicación.

**P: ¿Puedo usar otros componentes para selects?**
A: No, todo debe usar Select2 para mantener consistencia.

**P: ¿Dónde veo ejemplos?**
A: Mira `resources/views/managers/views/settings/database/edit.blade.php`
