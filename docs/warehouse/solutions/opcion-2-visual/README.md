# 🎨 Opción 2: Edición Visual Interactiva

## Objetivo

Permitir reposicionar y redimensionar estantes visualmente en la interfaz sin tocar código.

## 🎯 Resultado Final

```
USUARIO HACE CLICK EN "Editar Layout"
    ↓
INTERFAZ ENTRA EN MODO EDICIÓN
├─ Estantes tienen borde azul
├─ Cursor cambia a "move"
└─ Panel de edición visible
    ↓
USUARIO SELECCIONA UN ESTANTE
    ↓
[Se rellena formulario con dimensiones actuales]
    ↓
USUARIO MODIFICA ANCHO/ALTO/POSICIÓN
    ↓
HACE CLICK "GUARDAR"
    ↓
[API PUT actualiza BD]
    ↓
[SVG se redibuija automáticamente]
    ↓
CAMBIOS VISIBLES INMEDIATAMENTE
```

## 📋 Contenido de Esta Carpeta

- **dynamic-visual-layout-system.md** - Sistema completo documentado
- **migration.md** - Script SQL para agregar campos
- **model-updates.md** - Cambios en WarehouseLocation.php
- **controller-updates.md** - Nuevos métodos en controlador
- **blade-updates.md** - Interfaz de edición en vista
- **checklist.md** - Pasos paso a paso

## ⚠️ Prerequisito

**DEBE completarse OPCIÓN 1 primero**

Esta opción extiende la arquitectura dinámica.

## ✅ Checklist Rápido

- [ ] Completar Opción 1
- [ ] Leer dynamic-visual-layout-system.md
- [ ] Ejecutar migración SQL
- [ ] Agregar métodos a WarehouseLocation
- [ ] Agregar endpoints a WarehouseMapController
- [ ] Agregar UI de edición a vista Blade
- [ ] Probar creación/edición/reseteo
- [ ] Verificar guardado en BD

## 🚀 Tiempo Estimado

**4-5 horas** (después de completar Opción 1)

## 🗄️ Cambios a Base de Datos

Agregar 6 columnas a `warehouse_locations`:
- visual_width_m (float, nullable)
- visual_height_m (float, nullable)
- visual_position_x (float, nullable)
- visual_position_y (float, nullable)
- use_custom_visual (boolean, default: false)
- visual_rotation (float, default: 0)

## 📚 Documentación Completa

Para sistema completo, ver:
→ `/solutions/opcion-2-visual/dynamic-visual-layout-system.md`
