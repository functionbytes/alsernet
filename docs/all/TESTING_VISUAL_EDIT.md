# Testing Guide - Visual Editing System (Opción 2)

## Bugs Fixed

Three critical bugs were identified and fixed in the visual editing system:

### 1. **Variable Scope Bug** ✅ FIXED
- **Problem**: Variables `editMode` and `editingLocationUid` were declared AFTER event listeners were registered
- **Impact**: When clicking a shelf, `editMode` was undefined
- **Solution**: Moved declarations to global variables section (line 1894-1896)

### 2. **Variable Name Mismatch** ✅ FIXED
- **Problem**: Code used `LAYOUT_SPEC` (uppercase) but actual variable is `layoutSpec` (lowercase)
- **Impact**: `layoutSpec.find()` would fail when clicking shelf in edit mode
- **Solution**: Changed all references from `LAYOUT_SPEC` to `layoutSpec`

### 3. **Function Reference Error** ✅ FIXED
- **Problem**: Code called `renderWarehouse()` which doesn't exist
- **Impact**: After saving, map wouldn't re-render with new dimensions
- **Solution**: Changed to `drawFloorGroup(currentFloor)` which correctly redraws

---

## Testing Checklist

### ✅ Step 1: Load Warehouse Map
1. Navigate to warehouse map
2. **Expected**: Map loads without JavaScript errors
3. **Check Console**: Press F12, go to Console tab - should be empty (no red errors)

### ✅ Step 2: Click "Editar Layout" Button
1. Look for pencil icon `✎` in top toolbar
2. Click it
3. **Expected**:
   - Button becomes highlighted (blue glow)
   - All shelves get blue border and pointer cursor
   - Edit panel remains hidden until you select a shelf

### ✅ Step 3: Select a Shelf to Edit
1. Click on any blue-bordered shelf
2. **Expected**:
   - Edit panel appears on the right side
   - Form shows current values:
     - Ancho (width in meters)
     - Alto (height in meters)
     - Posición X
     - Posición Y
     - Rotación

### ✅ Step 4: Modify Dimensions
1. Change "Ancho" to a different value (e.g., 2.5 instead of 1.5)
2. Click "Guardar" button
3. **Expected**:
   - Success message appears
   - Shelf on map re-renders with new width
   - Map doesn't reload (only that shelf updates)
   - Form stays open for more edits

### ✅ Step 5: Verify Database Persistence
1. Open database tool (MySQL/PostgreSQL client)
2. Check `warehouse_locations` table
3. Find the shelf you edited (match by UID)
4. **Expected**: Column `visual_width_m` has the new value

### ✅ Step 6: Test Reset to Base Values
1. Still in edit mode with a shelf selected
2. Click "Resetear a Base" button
3. **Expected**:
   - Success message
   - Form values reset to style defaults
   - Shelf shrinks back to original size
   - Database `visual_width_m` becomes NULL
   - `use_custom_visual` becomes false

### ✅ Step 7: Test Multiple Edits
1. Edit multiple different shelves
2. Each should save independently
3. **Expected**: Each shelf shows its custom dimensions

### ✅ Step 8: Disable Edit Mode
1. Click "Editar Layout" button again
2. **Expected**:
   - Button loses highlight
   - Blue borders removed from shelves
   - Edit panel hidden
   - Shelves return to normal cursor

### ✅ Step 9: Test on Different Floors
1. Switch to a different floor from dropdown
2. Click "Editar Layout" again
3. Click shelves
4. **Expected**: Edit mode works on all floors independently

### ✅ Step 10: Verify Persistence After Refresh
1. Edit a shelf with custom dimensions
2. Save changes
3. Refresh the page (Cmd+R / Ctrl+R)
4. **Expected**: Custom dimensions persist (shelf still shows new size)

---

## What Each Button Does

| Button | Action |
|--------|--------|
| **Editar Layout** (pencil) | Toggle edit mode on/off |
| **Guardar** (in edit panel) | Save custom dimensions to database |
| **Resetear a Base** | Reset shelf to style defaults |

---

## Common Issues & Solutions

### Issue: "Cargando almacén..." keeps spinning
- **Cause**: JavaScript error on page load
- **Solution**: Check browser console (F12) for errors
- **Should be fixed**: Previous `warehouseUid` duplicate error is fixed

### Issue: Click shelf but nothing happens
- **Cause**: Edit mode not activated or variable not defined
- **Solution**:
  1. Click "Editar Layout" button first
  2. Wait for button highlight (blue glow)
  3. Check console for errors

### Issue: Edit panel shows but form is empty
- **Cause**: Shelf data not loaded from API
- **Solution**:
  1. Check network tab (F12 → Network)
  2. Look for `/api/layout` request
  3. Verify it returns 200 status with shelf data

### Issue: Save doesn't work
- **Cause**: API endpoint not responding
- **Solution**:
  1. Check browser console for error details
  2. Verify routes are registered: `php artisan route:list | grep visual`
  3. Check controller has both methods: `updateVisualConfig` and `resetVisualConfig`

---

## Files Modified in This Fix

```
resources/views/managers/views/warehouse/map/index.blade.php
  - Line 1894-1896: Moved editMode/editingLocationUid to global scope
  - Line 2677: Updated comment to reference new location
  - Line 2720: Changed LAYOUT_SPEC to layoutSpec
  - Line 2772-2779: Changed all LAYOUT_SPEC references
  - Line 2784: Changed renderWarehouse() to drawFloorGroup()
```

---

## Database Migration Status

Migration has been applied and columns exist:

```sql
ALTER TABLE warehouse_locations ADD COLUMN visual_width_m FLOAT DEFAULT NULL;
ALTER TABLE warehouse_locations ADD COLUMN visual_height_m FLOAT DEFAULT NULL;
ALTER TABLE warehouse_locations ADD COLUMN visual_position_x FLOAT DEFAULT NULL;
ALTER TABLE warehouse_locations ADD COLUMN visual_position_y FLOAT DEFAULT NULL;
ALTER TABLE warehouse_locations ADD COLUMN use_custom_visual BOOLEAN DEFAULT FALSE;
ALTER TABLE warehouse_locations ADD COLUMN visual_rotation FLOAT DEFAULT 0;
```

Run `php artisan migrate` if migration hasn't run yet.

---

## Next Steps

If all tests pass:
1. ✅ Feature is ready for production
2. ✅ All changes persisted in database
3. ✅ No performance issues

If tests fail:
1. Check browser console (F12) for specific error messages
2. Report the error with console output
3. Provide exact steps to reproduce

---

**Status**: ✅ READY FOR TESTING
**Last Updated**: 2025-12-02
**Bugs Fixed**: 3 critical issues resolved
