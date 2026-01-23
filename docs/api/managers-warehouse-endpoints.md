# Alsernet Warehouse Managers - API Endpoints

## Base URL
All endpoints are prefixed with `/manager/warehouse`

## Authentication
- **Required**: Yes (Laravel Sanctum)
- **Headers**: `Authorization: Bearer {token}`

---

## Location Styles Endpoints

### 1. Get All Available Location Styles
**Endpoint**: `GET /manager/warehouse/api/styles`

**Description**: Retrieves all available warehouse location styles from the `WarehouseLocationStyle` table.

**Method**: GET
**Auth Required**: Yes
**Response Code**: 200 OK

**Response Format**:
```json
{
  "success": true,
  "styles": [
    {
      "id": 1,
      "uid": "uuid-string",
      "code": "STY-1CARA-FRONT",
      "name": "Estilo 1 Cara Frontal",
      "type": "wall",
      "faces": ["front"],
      "description": "Estantería de una sola cara accesible desde el frente",
      "default_levels": 5,
      "default_sections": 4
    },
    {
      "id": 2,
      "uid": "uuid-string",
      "code": "STY-ISLA-2CARAS",
      "name": "Isla 2 Caras (Front-Back)",
      "type": "row",
      "faces": ["front", "back"],
      "description": "Isla accesible desde frente y parte posterior",
      "default_levels": 5,
      "default_sections": 3
    },
    {
      "id": 3,
      "uid": "uuid-string",
      "code": "SHELF-ISLAND",
      "name": "Estantería Isla (360°)",
      "type": "island",
      "faces": ["front", "back", "left", "right"],
      "description": "Isla accesible desde los 4 lados",
      "default_levels": 3,
      "default_sections": 2
    }
  ],
  "count": 3
}
```

**Error Response**:
```json
{
  "success": false,
  "message": "Error al obtener estilos: [error details]"
}
```

**Status Code**: 500 (on server error)

**Usage Example** (JavaScript with Axios):
```javascript
async function loadStyles() {
  try {
    const response = await axios.get('/manager/warehouse/api/styles');
    if (response.data.success) {
      response.data.styles.forEach(style => {
        console.log(`${style.name} - Type: ${style.type}`);
      });
    }
  } catch (error) {
    console.error('Error:', error.message);
  }
}
```

---

### 2. Get Style Details
**Endpoint**: `GET /manager/warehouse/api/styles/{style_id}`

**Description**: Retrieves detailed information for a specific style by ID.

**Parameters**:
- `style_id` (integer, required): The numeric ID of the style

**Method**: GET
**Auth Required**: Yes
**Response Code**: 200 OK

**Response Format**:
```json
{
  "success": true,
  "id": 1,
  "code": "STY-1CARA-FRONT",
  "name": "Estilo 1 Cara Frontal",
  "faces": ["front"],
  "faces_count": 1,
  "default_levels": 5,
  "default_sections": 4
}
```

**Error Response** (Not Found):
```json
{
  "message": "Not Found",
  "status": 404
}
```

---

## Location Creation Endpoint

### Create New Location
**Endpoint**: `POST /manager/warehouse/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/store`

**Description**: Creates a new warehouse location with specified style and sections.

**Parameters** (URL):
- `warehouse_uid` (UUID, required): Warehouse identifier
- `floor_uid` (UUID, required): Floor identifier

**Request Body**:
```json
{
  "code": "LOC-001",
  "style_id": 1,
  "position_x": 10.50,
  "position_y": 20.30,
  "available": true,
  "sections": [
    {
      "code": "LOC-001_FRONT",
      "face": "front",
      "level": 1
    }
  ],
  "visual_width_m": 1.50,
  "visual_height_m": 1.00,
  "use_custom_visual": true
}
```

**Required Fields**:
- `code` (string, max 50): Unique location code
- `style_id` (integer): Reference to WarehouseLocationStyle ID
- `position_x` (numeric): X coordinate in meters
- `position_y` (numeric): Y coordinate in meters
- `sections` (array): At least one section required

**Optional Fields**:
- `available` (boolean, default: true)
- `visual_width_m` (numeric): Visual width override in meters
- `visual_height_m` (numeric): Visual height override in meters
- `use_custom_visual` (boolean): Whether to use custom visual values

**Section Object** (within array):
- `code` (string, required, max 50): Section code
- `face` (string, required): One of: `front`, `back`, `left`, `right`
- `level` (integer, required, min 1): Shelf level number
- `barcode` (string, optional, max 100): Optional barcode

**Method**: POST
**Auth Required**: Yes
**Response Code**: 201 Created / 200 OK

**Success Response**:
```json
{
  "success": true,
  "location": {
    "id": 42,
    "uid": "location-uuid",
    "code": "LOC-001",
    "style_id": 1,
    "position_x": 10.50,
    "position_y": 20.30,
    "available": true,
    "sections": [
      {
        "id": 1,
        "location_id": 42,
        "code": "LOC-001_FRONT",
        "face": "front",
        "level": 1
      }
    ]
  }
}
```

**Validation Errors** (422):
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "code": ["El campo code es requerido"],
    "style_id": ["El campo style_id debe existir en la tabla warehouse_location_styles"],
    "position_x": ["El campo position_x debe ser un número"],
    "position_y": ["El campo position_y debe ser un número"],
    "sections": ["El campo sections es requerido"]
  }
}
```

**Usage Example** (JavaScript with Axios):
```javascript
async function createLocation(warehouseUid, floorUid, locationData) {
  try {
    const response = await axios.post(
      `/manager/warehouse/warehouses/${warehouseUid}/floors/${floorUid}/locations/store`,
      {
        code: 'LOC-A01',
        style_id: 1,
        position_x: 10.50,
        position_y: 20.30,
        available: true,
        sections: [
          {
            code: 'LOC-A01_FRONT',
            face: 'front',
            level: 1
          }
        ],
        visual_width_m: 1.50,
        visual_height_m: 1.00,
        use_custom_visual: true
      }
    );

    if (response.data.success) {
      console.log('Location created:', response.data.location);
    }
  } catch (error) {
    console.error('Error creating location:', error.response?.data?.errors || error.message);
  }
}
```

---

## Style Types Reference

| Type | Code | Name | Faces | Default Levels | Default Sections |
|------|------|------|-------|-----------------|------------------|
| `wall` | STY-1CARA-FRONT | Estilo 1 Cara Frontal | front | 5 | 4 |
| `row` | STY-ISLA-2CARAS | Isla 2 Caras | front, back | 5 | 3 |
| `island` | SHELF-ISLAND | Estantería Isla 360° | front, back, left, right | 3 | 2 |

**Face Values**: `front`, `back`, `left`, `right`

---

## Controller Methods

### WarehouseLocationStylesController

**File**: `/app/Http/Controllers/Managers/Warehouse/WarehouseLocationStylesController.php`

#### `apiGetAllStyles()`
- **Route**: `GET /manager/warehouse/api/styles`
- **Returns**: JSON response with all available styles
- **Filters**: Only returns styles where `available = true`
- **Orders By**: Style name (ascending)

---

## Routes Configuration

All routes are defined in `/routes/managers.php`:

```php
// Get all location styles
Route::get('/api/styles', [WarehouseLocationStylesController::class, 'apiGetAllStyles'])
    ->name('manager.warehouse.api.styles.all');

// Get specific style details
Route::get('/api/styles/{style_id}', [WarehouseLocationsController::class, 'getStyleDetails'])
    ->name('manager.warehouse.api.style.details');

// Create new location (within warehouse routes)
Route::post('/warehouses/{warehouse_uid}/floors/{floor_uid}/locations/store',
    [WarehouseLocationsController::class, 'store'])
    ->name('manager.warehouse.locations.store');
```

---

## Error Handling

### Common HTTP Status Codes

| Code | Meaning | Example |
|------|---------|---------|
| 200 | Success | GET styles returned successfully |
| 201 | Created | Location created successfully |
| 400 | Bad Request | Invalid JSON format |
| 401 | Unauthorized | Missing or invalid token |
| 404 | Not Found | Style ID doesn't exist |
| 422 | Validation Error | Missing required fields |
| 500 | Server Error | Database error |

### Exception Handling
The API endpoint catches exceptions and returns:
```json
{
  "success": false,
  "message": "Error al obtener estilos: [specific error]"
}
```

---

## Implementation Notes

### Location Creation Workflow

1. **Fetch Styles**: GET `/manager/warehouse/api/styles` to populate dropdown
2. **User Selection**: User selects style, which provides:
   - `style_id` (database ID)
   - `type` (row, island, wall)
   - `faces` (available faces for this style)
3. **Create Sections**: System automatically creates sections for each face
4. **Submit**: POST to `/locations/store` with all required data
5. **Response**: Returns created location with all sections

### Data Types & Rounding

- **Coordinates**: `position_x`, `position_y` - rounded to 2 decimals
- **Dimensions**: `visual_width_m`, `visual_height_m` - rounded to 2 decimals
- **Levels**: Integer values only

### Database Relations

```
WarehouseLocationStyle (1) ──→ (Many) WarehouseLocation
  └─ Has many locations

WarehouseLocation (1) ──→ (Many) WarehouseSection
  └─ Has many sections organized by face and level
```

---

## Frontend Integration Example

Complete example from warehouse map editor:

```javascript
// 1. Load styles when opening create modal
async function openCreateLocationModal() {
  $('#createLocationModal').show();
  await loadLocationStyles();
}

// 2. Fetch and populate dropdown
async function loadLocationStyles() {
  const response = await axios.get('/manager/warehouse/api/styles');
  const $select = $('#createLocationStyle');

  response.data.styles.forEach(style => {
    $select.append(
      `<option value="${style.id}" data-type="${style.type}">
        ${style.name} (${style.type})
      </option>`
    );
  });
}

// 3. Save location with selected style
async function saveNewLocation() {
  const styleId = $('#createLocationStyle').val();
  const styleType = $('#createLocationStyle option:selected').data('type');

  // Create sections based on style type
  const sections = createSectionsForStyle(styleType, code);

  // Submit to API
  await axios.post(
    `/manager/warehouse/warehouses/${warehouseUid}/floors/${floorUid}/locations/store`,
    {
      code: code,
      style_id: styleId,
      position_x: posX,
      position_y: posY,
      sections: sections,
      // ... other fields
    }
  );
}
```

---

**Last Updated**: 2025-12-02
**Status**: Active
**Version**: 1.0
