# Análisis Completo del Módulo Theme - Mercosan

## 📋 Resumen Ejecutivo

El módulo **Theme** es un sistema modular que gestiona la selección, activación y configuración de temas visuales en la plataforma Mercosan. La ruta `/admin/theme/all` muestra un catálogo de temas disponibles donde los administradores pueden:

- Visualizar todos los temas instalados
- Activar un tema como tema activo
- Eliminar temas no deseados
- Ver información del tema (autor, versión, descripción)
- Ver si un tema es heredado de otro

---

## 🏗️ Estructura del Módulo

```
/packages/theme/
├── src/
│   ├── Theme.php                          # Clase principal que maneja la lógica de temas
│   ├── Manager.php                         # Gestor que carga y registra temas
│   ├── Http/Controllers/
│   │   └── ThemeController.php            # Controlador admin (rutas /admin/theme/*)
│   ├── Providers/
│   │   ├── ThemeServiceProvider.php       # Registra el servicio
│   │   ├── RouteServiceProvider.php       # Registra las rutas
│   │   └── HookServiceProvider.php        # Hooks y eventos
│   ├── Forms/                              # Formularios para opciones del tema
│   │   ├── CustomCSSForm.php
│   │   ├── CustomJSForm.php
│   │   ├── CustomHTMLForm.php
│   │   └── RobotsTxtEditorForm.php
│   ├── Services/
│   │   └── ThemeService.php               # Lógica de negocio (activar, remover)
│   └── Facades/
│       ├── Theme.php                       # Acceso a Theme::
│       ├── Manager.php                     # Acceso a Manager::
│       ├── ThemeOption.php                # Acceso a ThemeOption::
│       └── AdminBar.php
│
├── routes/
│   ├── web.php                             # Rutas admin del panel
│   └── public.php                          # Rutas públicas
│
├── resources/
│   ├── views/
│   │   ├── list.blade.php                 # Vista del catálogo de temas
│   │   ├── options.blade.php              # Opciones/configuración del tema
│   │   ├── partials/
│   │   └── shortcodes/
│   ├── js/
│   │   └── theme.js                       # JavaScript para interacciones
│   └── lang/
│       └── en/theme.php                   # Traduciones
│
└── config/
    ├── general.php                         # Configuración del módulo
    └── permissions.php                     # Permisos requeridos
```

---

## 🔄 Flujo de `/admin/theme/all`

### 1️⃣ **Definición de la Ruta** (`routes/web.php`)

```php
Route::group(['prefix' => 'theme'], function (): void {
    Route::get('all', [
        'as' => 'theme.index',
        'uses' => 'ThemeController@index',
    ]);

    Route::post('active', [
        'as' => 'theme.active',
        'uses' => 'ThemeController@postActivateTheme',
        'middleware' => 'preventDemo',
        'permission' => 'theme.index',
    ]);

    Route::post('remove', [
        'as' => 'theme.remove',
        'uses' => 'ThemeController@postRemoveTheme',
        'middleware' => 'preventDemo',
        'permission' => 'theme.index',
    ]);
});
```

**Estructura:**
- Ruta **GET**: `/admin/theme/all` → `ThemeController@index`
- Ruta **POST**: `/admin/theme/active` → Activar un tema
- Ruta **POST**: `/admin/theme/remove` → Eliminar un tema

---

### 2️⃣ **Controlador (`ThemeController@index`)**

**Archivo:** `src/Http/Controllers/ThemeController.php` (línea 39-54)

```php
public function index()
{
    abort_unless(config('packages.theme.general.display_theme_manager_in_admin_panel', true), 404);

    $this->pageTitle(trans('packages/theme::theme.name'));

    if (File::exists(theme_path('.DS_Store'))) {
        File::delete(theme_path('.DS_Store'));
    }

    Assets::addScriptsDirectly('vendor/core/packages/theme/js/theme.js');

    $themes = Manager::getThemes();

    return view('packages/theme::list', compact('themes'));
}
```

**Pasos ejecutados:**

| Paso | Descripción |
|------|-------------|
| 1 | Verifica que el módulo está habilitado en configuración |
| 2 | Establece el título de página |
| 3 | Elimina archivos del sistema (`.DS_Store`) |
| 4 | Carga el archivo JavaScript `theme.js` |
| 5 | Obtiene todos los temas via `Manager::getThemes()` |
| 6 | Renderiza la vista `list.blade.php` con los temas |

---

### 3️⃣ **Manager - Obtiene los Temas** (`Manager.php`)

**Archivo:** `src/Manager.php`

```php
public function getAllThemes(): array
{
    $themes = [];
    foreach (BaseHelper::scanFolder(theme_path()) as $folder) {
        $jsonFile = $this->getThemeJsonPath($folder);

        $publicJsonFile = public_path('themes/' . ThemeFacade::getPublicThemeName() . '/theme.json');

        if (File::exists($publicJsonFile)) {
            $jsonFile = $publicJsonFile;
        }

        if (! File::exists($jsonFile)) {
            continue;
        }

        $theme = BaseHelper::getFileData($jsonFile);

        if (! empty($theme)) {
            $themeConfig = $this->themeService->getThemeConfig($folder);

            $themes[$folder] = $theme;
            $themes[$folder]['inherit'] = Arr::get($themeConfig, 'inherit');
        }
    }

    return $themes;
}
```

**Proceso:**

1. **Escanea la carpeta de temas** en `themes/`
2. **Para cada carpeta encontrada:**
   - Busca archivo `theme.json` en `themes/{tema}/theme.json`
   - Si existe en la carpeta pública, usa esa versión
   - Lee el contenido del JSON
3. **Enriquece datos:**
   - Lee la configuración del tema
   - Agrega información de herencia (`inherit`)
4. **Retorna array:**
   ```php
   [
       'default' => [
           'name' => 'Default Theme',
           'description' => '...',
           'author' => '...',
           'version' => '1.0.0',
           'url' => '...',
           'inherit' => null
       ],
       'premium' => [
           'name' => 'Premium Theme',
           'description' => '...',
           'author' => '...',
           'version' => '2.0.0',
           'inherit' => 'default'  // Hereda de "default"
       ]
   ]
   ```

---

### 4️⃣ **Vista - Renderiza el Catálogo** (`list.blade.php`)

**Archivo:** `resources/views/list.blade.php`

```blade
@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row row-cards mb-5">
        @foreach ($themes as $key => $theme)
            <div class="col-12 col-sm-6 col-lg-4">
                <x-core::card>
                    {{-- Ribbon si es tema heredado --}}
                    @if ($inherit = Arr::get($theme, 'inherit'))
                        <div class="ribbon bg-red">
                            {{ trans('packages/theme::theme.child_of', ['theme' => ...]) }}
                        </div>
                    @endif

                    {{-- Screenshot del tema --}}
                    <div class="img-responsive img-responsive-4x3 card-img-top border-bottom"
                         style="background-image: url('{{ Theme::getThemeScreenshot($key) }}')">
                    </div>

                    <x-core::card.body>
                        {{-- Nombre --}}
                        <h4 class="card-title text-truncate mb-2">
                            {{ $theme['name'] }}
                        </h4>

                        {{-- Descripción --}}
                        @if (! empty($theme['description']))
                            <p class="text-secondary text-truncate">
                                {{ $theme['description'] }}
                            </p>
                        @endif

                        {{-- Autor y versión --}}
                        <div class="row g-1 g-lg-0">
                            @if (! empty($theme['author']))
                                <div class="col-12 col-lg">
                                    {{ trans('packages/theme::theme.author') }}:
                                    @if (! empty($theme['url']))
                                        <a href="{{ $theme['url'] }}" target="_blank">
                                            {{ $theme['author'] }}
                                        </a>
                                    @else
                                        <strong>{{ $theme['author'] }}</strong>
                                    @endif
                                </div>
                            @endif
                            @if (! empty($theme['version']))
                                <div class="col-12 col-lg-auto">
                                    {{ trans('packages/theme::theme.version') }}:
                                    <strong>{{ $theme['version'] }}</strong>
                                </div>
                            @endif
                        </div>
                    </x-core::card.body>

                    {{-- Botones de acción --}}
                    <x-core::card.footer>
                        <div class="btn-list">
                            @if (setting('theme') && Theme::getThemeName() == $key)
                                {{-- Si es el tema activo: botón deshabilitado --}}
                                <x-core::button
                                    type="button"
                                    color="info"
                                    :disabled="true"
                                    icon="ti ti-check"
                                >
                                    {{ trans('packages/theme::theme.activated') }}
                                </x-core::button>
                            @else
                                {{-- Si no es el tema activo: botones Activar y Eliminar --}}
                                @if (Auth::guard()->user()->hasPermission('theme.activate'))
                                    <x-core::button
                                        type="button"
                                        color="primary"
                                        icon="ti ti-check"
                                        class="btn-trigger-active-theme"
                                        :data-url="route('theme.active', ['theme' => $key])"
                                        data-theme="{{ $key }}"
                                    >
                                        {{ trans('packages/theme::theme.active') }}
                                    </x-core::button>
                                @endif
                                @if (Auth::guard()->user()->hasPermission('theme.remove'))
                                    <x-core::button
                                        type="button"
                                        icon="ti ti-trash"
                                        class="btn-trigger-remove-theme"
                                        :data-url="route('theme.remove', ['theme' => $key])"
                                        data-theme="{{ $key }}"
                                    >
                                        {{ trans('packages/theme::theme.remove') }}
                                    </x-core::button>
                                @endif
                            @endif
                        </div>
                    </x-core::card.footer>
                </x-core::card>
            </div>
        @endforeach
    </div>
@stop

@push('footer')
    {{-- Modal de confirmación para eliminar --}}
    <x-core::modal.action
        id="remove-theme-modal"
        type="danger"
        :title="trans('packages/theme::theme.remove_theme')"
        :description="trans('packages/theme::theme.remove_theme_confirm_message')"
        :submit-button-attrs="['id' => 'confirm-remove-theme-button']"
        :submit-button-label="trans('packages/theme::theme.remove_theme_confirm_yes')"
    />
@endpush
```

**Estructura HTML:**

```
┌─ Grid Row (12 columnas)
│
├─ Para cada tema: columna (col-12 col-sm-6 col-lg-4)
│  └─ Card Bootstrap
│     ├─ Ribbon (si es heredado)
│     ├─ Screenshot del tema
│     ├─ Nombre y descripción
│     ├─ Autor y versión
│     └─ Botones:
│        ├─ "ACTIVADO" (si es activo, deshabilitado)
│        ├─ "ACTIVAR" (si no es activo, clase .btn-trigger-active-theme)
│        └─ "ELIMINAR" (clase .btn-trigger-remove-theme)
│
└─ Modal de confirmación (id: remove-theme-modal)
```

---

## 🎯 Interacciones JavaScript (`theme.js`)

**Archivo:** `resources/js/theme.js`

### **1. Activar un Tema**

```javascript
$(document).on('click', '.btn-trigger-active-theme', (event) => {
    event.preventDefault()
    let _self = $(event.currentTarget)
    Botble.showButtonLoading(_self)  // Muestra spinner

    $httpClient
        .make()
        .post(_self.data('url'))  // POST a /settings/theme/active
        .then(({ data }) => {
            Botble.showSuccess(data.message)
            window.location.reload()  // Recarga la página
        })
        .finally(() => {
            Botble.hideButtonLoading(_self)
        })
})
```

**Flujo:**
1. Usuario hace clic en botón "ACTIVAR"
2. Se muestra spinner de carga
3. Se envía POST a `/admin/theme/active?theme={nombre}`
4. Si es exitoso, muestra notificación y recarga la página
5. Se esconde el spinner

---

### **2. Eliminar un Tema**

```javascript
$(document).on('click', '.btn-trigger-remove-theme', (event) => {
    event.preventDefault()
    let _self = $(event.currentTarget)
    $('#confirm-remove-theme-button')
        .data('theme', _self.data('theme'))
        .data('url', _self.data('url'))

    $('#remove-theme-modal').modal('show')
})
```

**Flujo:**
1. Usuario hace clic en botón "ELIMINAR"
2. Se abre modal de confirmación
3. Se guardan los datos del tema en el botón de confirmación

---

### **3. Confirmar Eliminación**

```javascript
$(document).on('click', '#confirm-remove-theme-button', (event) => {
    event.preventDefault()
    let _self = $(event.currentTarget)
    Botble.showButtonLoading(_self)

    $httpClient
        .make()
        .post(_self.data('url'))  // POST a /settings/theme/remove
        .then(({ data }) => {
            Botble.showSuccess(data.message)
            window.location.reload()
        })
        .finally(() => {
            Botble.hideButtonLoading(_self)
            $('#remove-theme-modal').modal('hide')
        })
})
```

**Flujo:**
1. Usuario confirma en el modal
2. Se muestra spinner
3. Se envía POST a `/admin/theme/remove?theme={nombre}`
4. Si es exitoso, muestra notificación y recarga
5. Se cierra el modal

---

## 🎮 Rutas POST - Acciones

### **POST `/admin/theme/active` - Activar Tema**

**Controlador:** `ThemeController::postActivateTheme()`

```php
public function postActivateTheme(Request $request, ThemeService $themeService)
{
    abort_unless(config('packages.theme.general.display_theme_manager_in_admin_panel', true), 404);

    $result = $themeService->activate($request->input('theme'));

    return $this
        ->httpResponse()
        ->setError($result['error'])
        ->setMessage($result['message']);
}
```

**Qué hace:**
1. Verifica que el módulo está habilitado
2. Llama a `ThemeService::activate()` con el nombre del tema
3. Retorna respuesta JSON con `error` y `message`

---

### **POST `/admin/theme/remove` - Eliminar Tema**

**Controlador:** `ThemeController::postRemoveTheme()`

```php
public function postRemoveTheme(Request $request, ThemeService $themeService)
{
    abort_unless(config('packages.theme.general.display_theme_manager_in_admin_panel', true), 404);

    $theme = strtolower($request->input('theme'));

    if (in_array($theme, BaseHelper::scanFolder(theme_path()))) {
        try {
            $result = $themeService->remove($theme);

            return $this
                ->httpResponse()
                ->setError($result['error'])
                ->setMessage($result['message']);
        } catch (Exception $exception) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    return $this
        ->httpResponse()
        ->setError()
        ->setMessage(trans('packages/theme::theme.theme_is_not_existed'));
}
```

**Qué hace:**
1. Verifica que el módulo está habilitado
2. Obtiene nombre del tema en minúsculas
3. Verifica que el tema existe
4. Llama a `ThemeService::remove()` para eliminarlo
5. Retorna respuesta JSON con resultado

---

## 🔐 Seguridad y Permisos

### **Permisos Requeridos**

```php
// Ruta base (listar y activar)
'permission' => 'theme.index'

// Eliminar
'permission' => 'theme.index'

// Middleware
'middleware' => 'preventDemo'  // Evita cambios en modo demo
```

### **Verificaciones en Controlador**

```php
// En vista, verifica permisos por usuario
@if (Auth::guard()->user()->hasPermission('theme.activate'))
@if (Auth::guard()->user()->hasPermission('theme.remove'))
```

---

## 📁 Estructura de Carpetas de Temas

Cada tema debe tener esta estructura:

```
themes/
└── nombre-tema/
    ├── theme.json          # Metadatos del tema (OBLIGATORIO)
    ├── screenshot.png      # Captura de pantalla
    ├── config.php          # Configuración específica del tema
    ├── layouts/            # Layouts Blade
    │   ├── default.blade.php
    │   └── ...
    ├── partials/           # Parciales Blade
    │   ├── header.blade.php
    │   └── ...
    ├── views/              # Vistas Blade
    │   └── ...
    └── assets/             # CSS, JS, imágenes
        ├── css/
        ├── js/
        └── images/
```

### **Ejemplo `theme.json`**

```json
{
    "name": "Premium Theme",
    "description": "Una descripción del tema",
    "author": "Tu Empresa",
    "url": "https://ejemplo.com",
    "version": "2.0.0",
    "presets": []
}
```

---

## 🎨 Configuración (`config/general.php`)

```php
return [
    'themeDefault' => 'default',           // Tema por defecto
    'layoutDefault' => 'default',          // Layout por defecto
    'themeDir' => 'themes',                // Carpeta de temas
    'containerDir' => [
        'layout' => 'layouts',             // Subcarpeta de layouts
        'asset' => '',                     // Subcarpeta de assets
        'partial' => 'partials',           // Subcarpeta de partiales
        'view' => 'views',                 // Subcarpeta de vistas
    ],
    'enable_custom_js' => true,            // Permitir JS personalizado
    'enable_custom_html' => true,          // Permitir HTML personalizado
    'enable_robots_txt_editor' => true,    // Editor de robots.txt
    'display_theme_manager_in_admin_panel' => true,  // Mostrar administrador
];
```

---

## 💾 Bases de Datos - Tabla `settings`

Los temas se guardan en la tabla `settings`:

```sql
SELECT * FROM settings WHERE key LIKE 'theme%';

+-------+----------+----------+
| id    | key      | value    |
+-------+----------+----------+
| 1     | theme    | default  | ← Tema activo
| 2     | layout   | default  | ← Layout activo
+-------+----------+----------+
```

**Métodos helper:**

```php
setting('theme')           // Obtiene el tema activo
setting('layout')          // Obtiene el layout activo
Theme::getThemeName()      // Nombre del tema actual
Theme::getPublicThemeName() // Nombre público del tema
```

---

## 🔗 Relación con Otros Módulos

| Módulo | Interacción |
|--------|------------|
| **Base** | Proporciona `BaseHelper`, `AdminHelper`, respuestas HTTP |
| **Setting** | Almacena y recupera la configuración activa |
| **Media** | Gestiona screenshots y assets del tema |
| **Admin** | Layouts y componentes de la interfaz admin |

---

## 📝 Flujo Completo: De Clic a Cambio de Tema

```
1. Usuario hace clic en "ACTIVAR" en tema "premium"
   └─ Evento click en .btn-trigger-active-theme

2. JavaScript prepara POST request
   └─ URL: /admin/theme/active
   └─ Data: theme=premium

3. Servidor procesa POST
   └─ ThemeController::postActivateTheme()
   └─ Llama ThemeService::activate('premium')
   └─ ThemeService::activate():
      ├─ Valida que el tema existe
      ├─ Actualiza DB: UPDATE settings SET value='premium' WHERE key='theme'
      ├─ Limpia caché
      └─ Retorna { error: false, message: 'Tema activado' }

4. JavaScript recibe respuesta exitosa
   └─ Muestra notificación de éxito
   └─ window.location.reload()

5. Página se recarga
   └─ ThemeController::index() se ejecuta nuevamente
   └─ Manager::getThemes() obtiene los temas
   └─ La vista lista muestra "ACTIVADO" en tema "premium"
```

---

## 🚀 Características Adicionales

### **Temas Heredados**

Un tema puede heredar de otro:

```php
// En config.php del tema hijo
'inherit' => 'default'

// En la vista, muestra ribbon rojo
"Tema hijo de: default"
```

**Ventaja:** El tema hijo puede sobrescribir archivos (layouts, partials) pero heredar los del padre.

---

### **Screenshots Base64**

```php
// En Theme::getThemeScreenshot()
// Retorna la imagen como data:// URI en Base64
return 'data:image/png;base64,' . base64_encode(File::get($screenshot));
```

**Ventaja:** No necesita URL externa, se embebe en HTML.

---

### **Validaciones**

```php
// Verifica que el módulo está habilitado
abort_unless(config('packages.theme.general.display_theme_manager_in_admin_panel', true), 404);

// Verifica que el tema existe en el servidor
in_array($theme, BaseHelper::scanFolder(theme_path()))

// Verifica que el archivo JSON existe
File::exists($jsonFile)
```

---

## 📊 Diagrama de Flujo

```
┌─────────────────────────────────────────┐
│  GET /admin/theme/all                   │
└──────────────────┬──────────────────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │  ThemeController@    │
        │    index()           │
        └──────────────────────┘
                   │
        ┌──────────┴──────────┐
        │                     │
        ▼                     ▼
  ┌──────────────┐   ┌──────────────────┐
  │ Manager::    │   │ Assets::addScripts│
  │ getThemes()  │   │ (theme.js)       │
  │              │   └──────────────────┘
  │ ├─ Escanea   │
  │ │ themes/    │
  │ ├─ Lee       │
  │ │ theme.json │
  │ └─ Retorna   │
  │   array      │
  └──────────────┘
        │
        ▼
  ┌──────────────────────┐
  │  list.blade.php      │
  │                      │
  │ ├─ Itera temas       │
  │ ├─ Crea tarjetas     │
  │ ├─ Botones (click)   │
  │ └─ Modal eliminar    │
  └──────────────────────┘
        │
        ▼
  ┌──────────────────────────────────┐
  │  JavaScript - theme.js           │
  │                                  │
  │ Evento: click .btn-trigger-*     │
  │   │                              │
  │   ├─ Activar: POST /admin/theme/ │
  │   │           active?theme=...   │
  │   │   └─ Recarga página          │
  │   │                              │
  │   └─ Eliminar: POST /admin/theme/│
  │               remove?theme=...   │
  │       └─ Muestra modal, confirma │
  │       └─ Recarga página          │
  └──────────────────────────────────┘
```

---

## 🎯 Casos de Uso

### **Caso 1: Cambiar el Tema**

```
Admin user → Click "ACTIVAR" en tema "premium"
→ POST /admin/theme/active?theme=premium
→ ThemeService valida y actualiza DB
→ Página se recarga
→ setting('theme') ahora retorna 'premium'
→ Theme::getThemeName() retorna 'premium'
→ Botón muestra "ACTIVADO"
```

### **Caso 2: Eliminar un Tema**

```
Admin user → Click "ELIMINAR" en tema "oldtheme"
→ Modal: "¿Está seguro?"
→ Click "CONFIRMAR"
→ POST /admin/theme/remove?theme=oldtheme
→ ThemeService elimina carpeta
→ Página se recarga
→ Tema desaparece del catálogo
```

### **Caso 3: Ver Tema Heredado**

```
Vista muestra:
┌─ Premium Theme (child_of default) ◄─ Ribbon rojo
│  Screenshot
│  Por: Tu Empresa v2.0.0
│
│  [ ACTIVAR ] [ ELIMINAR ]
└─
```

---

## 🔧 Extensión del Sistema

### **Agregar un Nuevo Tema**

1. **Crear carpeta:** `themes/mi-tema/`
2. **Crear `theme.json`:**
   ```json
   {
       "name": "Mi Tema",
       "description": "Descripción",
       "author": "Tu Nombre",
       "version": "1.0.0"
   }
   ```
3. **Crear estructura:**
   ```
   mi-tema/
   ├── layouts/default.blade.php
   ├── partials/header.blade.php
   ├── views/home.blade.php
   └── assets/css/style.css
   ```
4. **Visitar `/admin/theme/all`** - El tema aparece automáticamente

---

## 📚 Archivos Clave

| Archivo | Línea | Función |
|---------|-------|---------|
| `routes/web.php` | 10-28 | Rutas GET/POST |
| `ThemeController.php` | 39-54 | Acción index |
| `Manager.php` | 29-56 | Obtener temas |
| `list.blade.php` | 1-100 | Renderizar catálogo |
| `theme.js` | 1-52 | Interacciones |
| `config/general.php` | 1-83 | Configuración |
| `Theme.php` | 62-1114 | Lógica principal |

---

## 🐛 Posibles Mejoras

1. **Caché:** Cachear los temas obtenidos en `Manager::getThemes()`
2. **Búsqueda:** Agregar filtro/búsqueda de temas
3. **Vista previa:** Modal con vista previa antes de activar
4. **Versionado:** Validar compatibilidad de versiones
5. **Notificaciones:** Sistema de notificaciones en tiempo real

---

## 📖 Referencias

- **Configuración:** `packages/theme/config/general.php`
- **Permisos:** `packages/theme/config/permissions.php`
- **Idioma:** `packages/theme/resources/lang/en/theme.php`
- **Servicios:** `packages/theme/src/Services/ThemeService.php`

