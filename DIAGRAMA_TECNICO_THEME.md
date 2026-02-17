# 🔧 Diagrama Técnico - Módulo Theme

## 📐 Arquitectura General

```
┌─────────────────────────────────────────────────────────────────┐
│                        NAVEGADOR (BROWSER)                       │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │   GET /admin/theme/all                                   │   │
│  │   JavaScript: theme.js                                   │   │
│  │   POST /admin/theme/active | /admin/theme/remove        │   │
│  └──────────────────────────────────────────────────────────┘   │
└────────────────────────┬──────────────────────────────────────────┘
                         │ HTTP
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│                    SERVIDOR LARAVEL                              │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ Route::group(['prefix' => 'theme'], function () {       │   │
│  │   Route::get('all', ThemeController@index)              │   │
│  │   Route::post('active', ThemeController@postActivate)   │   │
│  │   Route::post('remove', ThemeController@postRemove)     │   │
│  └──────────────────────────────────────────────────────────┘   │
│                         │                                        │
│                         ▼                                        │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │         ThemeController                                  │   │
│  │  ┌──────────────────────────────────────────────────┐   │   │
│  │  │ index()                                          │   │   │
│  │  │ ├─ Manager::getThemes()                         │   │   │
│  │  │ └─ return view('list', compact('themes'))       │   │   │
│  │  │                                                  │   │   │
│  │  │ postActivateTheme(Request $req, Service $svc)   │   │   │
│  │  │ ├─ $svc->activate($req->input('theme'))         │   │   │
│  │  │ └─ return httpResponse()                        │   │   │
│  │  │                                                  │   │   │
│  │  │ postRemoveTheme(Request $req, Service $svc)     │   │   │
│  │  │ ├─ $svc->remove($req->input('theme'))           │   │   │
│  │  │ └─ return httpResponse()                        │   │   │
│  │  └──────────────────────────────────────────────────┘   │   │
│  └──────────────────────────────────────────────────────────┘   │
│                         │                                        │
│          ┌──────────────┼──────────────┐                        │
│          ▼              ▼              ▼                        │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐            │
│  │   Manager    │ │ ThemeService │ │ View Engine  │            │
│  │              │ │              │ │              │            │
│  │ getThemes()  │ │ activate()   │ │ list.blade   │            │
│  │ - Scan files │ │ - DB update  │ │ - For loop   │            │
│  │ - Read JSON  │ │ - Cache      │ │ - Cards HTML │            │
│  │ - Parse meta │ │ - Validate   │ │ - Buttons    │            │
│  └──────────────┘ └──────────────┘ └──────────────┘            │
│          │              │                   │                  │
│          ▼              ▼                   ▼                  │
│  ┌────────────────────────────────────────────────────────┐   │
│  │              FILE SYSTEM & DATABASE                     │   │
│  │                                                         │   │
│  │  📁 themes/                                            │   │
│  │     ├── default/                                       │   │
│  │     │   ├── theme.json                                │   │
│  │     │   ├── layouts/                                  │   │
│  │     │   ├── partials/                                 │   │
│  │     │   └── assets/                                   │   │
│  │     │                                                  │   │
│  │     ├── premium/                                       │   │
│  │     │   ├── theme.json                                │   │
│  │     │   └── ...                                        │   │
│  │     │                                                  │   │
│  │     └── modern/                                        │   │
│  │         └── ...                                        │   │
│  │                                                         │   │
│  │  🗄️ Database: settings table                           │   │
│  │     ├── id | key   | value                            │   │
│  │     ├── 1  | theme | 'premium' ← Tema activo          │   │
│  │     └── 2  | layout| 'default'                        │   │
│  └────────────────────────────────────────────────────────┘   │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Flujo de Datos - GET /admin/theme/all

```
┌─────────────────────────────────────────────────────────┐
│ HTTP GET /admin/theme/all                               │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ Router (routes/web.php:10)                              │
│ Route::get('all', ThemeController@index)                │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ ThemeController::index()                                │
│                                                         │
│ 1. abort_unless(config(...), 404)                      │
│    └─ Verifica que module esté habilitado              │
│                                                         │
│ 2. $this->pageTitle('Temas')                           │
│    └─ Establece título de página                       │
│                                                         │
│ 3. File::delete(theme_path('.DS_Store'))               │
│    └─ Limpia archivos del sistema                      │
│                                                         │
│ 4. Assets::addScriptsDirectly(theme.js)                │
│    └─ Carga JavaScript de interacciones                │
│                                                         │
│ 5. $themes = Manager::getThemes()                      │
│    └─ Obtiene array de temas (→ véase abajo)           │
│                                                         │
│ 6. return view('packages/theme::list', compact('themes'))
│    └─ Renderiza vista Blade con los temas              │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ Manager::getThemes() → Manager::getAllThemes()         │
│                                                         │
│ foreach (BaseHelper::scanFolder(theme_path()) as $folder) {
│   // Itera cada carpeta en themes/
│                                                         │
│   $jsonFile = theme_path($folder . '/theme.json')      │
│   // Busca theme.json                                  │
│                                                         │
│   if (!File::exists($jsonFile)) continue               │
│   // Si no existe, salta esta carpeta                  │
│                                                         │
│   $theme = BaseHelper::getFileData($jsonFile)          │
│   // Lee y parsea el JSON                              │
│                                                         │
│   $themeConfig = $this->themeService->getThemeConfig() │
│   // Obtiene configuración adicional                   │
│                                                         │
│   $themes[$folder] = $theme                            │
│   $themes[$folder]['inherit'] = $themeConfig['inherit']
│   // Agrega información de herencia                    │
│ }                                                       │
│                                                         │
│ return $themes                                          │
│ // Array con todos los temas                           │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────┐
│ Blade Template: list.blade.php                          │
│                                                         │
│ @extends(BaseHelper::getAdminMasterLayoutTemplate())   │
│ └─ Usa layout admin general                            │
│                                                         │
│ @section('content')                                     │
│   <div class="row row-cards mb-5">                     │
│     @foreach ($themes as $key => $theme)               │
│       ├─ $key   = 'default', 'premium', etc.           │
│       ├─ $theme = array con nombre, desc, autor, etc.  │
│       │                                                 │
│       <div class="col-12 col-sm-6 col-lg-4">           │
│       │ <!-- Card Bootstrap para cada tema -->         │
│       │                                                 │
│       │ @if ($inherit = Arr::get($theme, 'inherit'))   │
│       │   <div class="ribbon">                         │
│       │     "Tema hijo de: {{ $inherit }}"             │
│       │   </div>                                        │
│       │                                                 │
│       │ <img src="{{ Theme::getThemeScreenshot($key) }}
│       │      alt="{{ $theme['name'] }}" />             │
│       │                                                 │
│       │ <h4>{{ $theme['name'] }}</h4>                  │
│       │ <p>{{ $theme['description'] }}</p>             │
│       │                                                 │
│       │ <div>{{ $theme['author'] }} v{{ $theme['version'] }}
│       │ </div>                                          │
│       │                                                 │
│       │ @if (Theme::getThemeName() == $key)            │
│       │   <!-- Es el tema activo -->                   │
│       │   <button disabled>[ACTIVADO]</button>         │
│       │ @else                                           │
│       │   <!-- No es el tema activo -->                │
│       │   <button class="btn-trigger-active-theme"     │
│       │           data-url="/admin/theme/active"       │
│       │           data-theme="{{ $key }}">             │
│       │     [ACTIVAR]                                  │
│       │   </button>                                     │
│       │                                                 │
│       │   <button class="btn-trigger-remove-theme"     │
│       │           data-url="/admin/theme/remove"       │
│       │           data-theme="{{ $key }}">             │
│       │     [ELIMINAR]                                 │
│       │   </button>                                     │
│       │ @endif                                          │
│       </div>                                            │
│     @endforeach                                         │
│   </div>                                                │
│ @endsection                                             │
└────────────────┬────────────────────────────────────────┘
                 │
                 ▼
        ┌─────────────────────┐
        │ HTML enviado al     │
        │ navegador (browser) │
        └─────────────────────┘
```

---

## 🖱️ Flujo de Interacción - POST /admin/theme/active

```
┌──────────────────────────────────────────────────────────┐
│ Usuario hace clic en botón [ACTIVAR]                     │
│ class="btn-trigger-active-theme"                         │
│ data-url="/admin/theme/active?theme=premium"             │
└──────────────┬───────────────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────────────┐
│ JavaScript: theme.js (línea 3-18)                        │
│                                                          │
│ $(document).on('click', '.btn-trigger-active-theme', ...) {
│   event.preventDefault()                                │
│   let _self = $(event.currentTarget)                    │
│   // Obtiene referencia al botón clickeado              │
│                                                          │
│   Botble.showButtonLoading(_self)                       │
│   // Muestra spinner de carga en el botón               │
│                                                          │
│   $httpClient                                           │
│     .make()                                             │
│     .post(_self.data('url'))  // /admin/theme/active    │
│     .then(({ data }) => {     // Si éxito               │
│       Botble.showSuccess(data.message)                  │
│       // Muestra notificación verde                     │
│       window.location.reload()                          │
│       // Recarga la página                              │
│     })                                                   │
│     .finally(() => {                                    │
│       Botble.hideButtonLoading(_self)                  │
│       // Esconde spinner en cualquier caso             │
│     })                                                   │
│ })                                                       │
└──────────────┬───────────────────────────────────────────┘
               │
               ▼ (HTTP POST Request)
┌──────────────────────────────────────────────────────────┐
│ POST /admin/theme/active                                 │
│ Data: { theme: 'premium' }                               │
│ Headers: { X-CSRF-TOKEN: ... }                           │
└──────────────┬───────────────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────────────┐
│ Laravel Middleware Chain (routes/web.php:15)             │
│                                                          │
│ ├─ web (sesiones, cookies, etc)                        │
│ ├─ core (middleware personalizado)                      │
│ ├─ preventDemo (bloquea si es sitio demo)              │
│ ├─ auth (verifica usuario autenticado)                 │
│ └─ authorize (verifica permiso 'theme.index')          │
│    └─ Si falla: 403 Forbidden                          │
└──────────────┬───────────────────────────────────────────┘
               │ Si todo OK ↓
┌──────────────────────────────────────────────────────────┐
│ ThemeController::postActivateTheme()                     │
│                                                          │
│ 1. abort_unless(config(..., true), 404)                │
│    └─ Verifica nuevamente que módulo esté habilitado    │
│                                                          │
│ 2. $result = $themeService->activate(                   │
│              $request->input('theme')  // 'premium'      │
│            )                                             │
│    └─ Llama servicio para activar tema                  │
└──────────────┬───────────────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────────────┐
│ ThemeService::activate('premium')                        │
│                                                          │
│ 1. Valida que el tema existe                           │
│    if (!$this->themeExists('premium')) {                │
│      return ['error' => true, 'message' => '...']       │
│    }                                                     │
│                                                          │
│ 2. Obtiene el tema                                      │
│    $theme = $this->getTheme('premium')                 │
│                                                          │
│ 3. Valida dependencias                                  │
│    if ($theme['inherit'] && !$this->themeExists(...)) { │
│      return ['error' => true, ...]                      │
│    }                                                     │
│                                                          │
│ 4. ACTUALIZA LA BASE DE DATOS                           │
│    Setting::set('theme', 'premium')                     │
│    // Ejecuta: UPDATE settings SET value='premium'      │
│    //          WHERE key='theme'                        │
│                                                          │
│ 5. Limpia caché                                         │
│    Cache::clear()                                       │
│                                                          │
│ 6. Dispara evento                                       │
│    event(new ThemeChanged('premium'))                   │
│                                                          │
│ 7. Retorna éxito                                        │
│    return [                                             │
│      'error' => false,                                  │
│      'message' => trans('packages/theme::theme.changed')│
│    ]                                                     │
└──────────────┬───────────────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────────────┐
│ ThemeController (continuación)                           │
│                                                          │
│ return $this                                            │
│   ->httpResponse()                                      │
│   ->setError($result['error'])      // false            │
│   ->setMessage($result['message'])  // 'Tema cambiado'  │
│   // Retorna JSON:                                      │
│   // { error: false, message: 'Tema cambiado...' }      │
└──────────────┬───────────────────────────────────────────┘
               │
               ▼ (HTTP Response)
┌──────────────────────────────────────────────────────────┐
│ JSON Response                                            │
│ {                                                        │
│   "error": false,                                        │
│   "message": "Tema activo ha sido cambiado...",         │
│   "data": null                                           │
│ }                                                        │
└──────────────┬───────────────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────────────┐
│ JavaScript: .then(({ data }) => { ... })                │
│                                                          │
│ if (!data.error) {                                       │
│   Botble.showSuccess(                                   │
│     "Tema activo ha sido cambiado..."                   │
│   )                                                      │
│   // Muestra notificación verde tipo toast              │
│                                                          │
│   window.location.reload()                              │
│   // Recarga la página                                  │
│ }                                                        │
└──────────────┬───────────────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────────────┐
│ Browser recarga la página                                │
│ GET /admin/theme/all (nuevamente)                        │
└──────────────┬───────────────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────────────┐
│ ThemeController::index() se ejecuta NUEVAMENTE           │
│                                                          │
│ $themes = Manager::getThemes()                          │
│ // setting('theme') AHORA retorna 'premium'             │
│ // (porque lo actualizamos en la BD)                    │
│                                                          │
│ return view('packages/theme::list', compact('themes'))  │
│ // La vista renderiza, y para el tema 'premium':        │
│ // Theme::getThemeName() == 'premium' ✓ TRUE            │
│ // Muestra: <button disabled>[ACTIVADO]</button>        │
└──────────────┬───────────────────────────────────────────┘
               │
               ▼
        ┌─────────────────────────────────────┐
        │ HTML actualizado enviado al browser │
        │ Premium ahora muestra [ACTIVADO]     │
        │ Default/Modern muestran [ACTIVAR]   │
        └─────────────────────────────────────┘
```

---

## 📦 Manager - Algoritmo Detallado

```
Manager::getAllThemes()
│
├─ foreach (BaseHelper::scanFolder(theme_path()) as $folder)
│  │
│  └─ Para cada carpeta en /themes:
│     │
│     ├─ $jsonFile = theme_path() . '/' . $folder . '/theme.json'
│     │  └─ Path: /themes/default/theme.json
│     │
│     ├─ $publicJsonFile = public_path('themes/' . publicTheme . '/theme.json')
│     │  └─ Path: /public/themes/default/theme.json
│     │  └─ Si existe, la usa (versión pública)
│     │
│     ├─ if (!File::exists($jsonFile)) continue
│     │  └─ Si no existe JSON, salta esta carpeta
│     │
│     ├─ $theme = BaseHelper::getFileData($jsonFile)
│     │  └─ Lee file_get_contents($jsonFile)
│     │  └─ json_decode($content, true)
│     │  └─ Retorna array PHP
│     │
│     ├─ if (!empty($theme))
│     │  │
│     │  ├─ $themeConfig = $this->themeService->getThemeConfig($folder)
│     │  │  └─ Lee config.php del tema si existe
│     │  │
│     │  ├─ $themes[$folder] = $theme
│     │  │  └─ Agrega al array con clave = nombre carpeta
│     │  │
│     │  ├─ $themes[$folder]['inherit'] = Arr::get($themeConfig, 'inherit')
│     │  │  └─ Agrega información de herencia
│     │  │
│     │  └─ Ejemplo de tema agregado:
│     │     [
│     │       'default' => [
│     │         'name' => 'Default Theme',
│     │         'description' => '...',
│     │         'author' => 'Botble',
│     │         'version' => '1.0.0',
│     │         'inherit' => null
│     │       ]
│     │     ]
│
└─ return $themes
   └─ Array con todos los temas encontrados
```

---

## 🔐 Validaciones y Seguridad

```
GET /admin/theme/all
│
├─ Middleware: web, core
│  └─ Session + CSRF protection
│
├─ Autenticación: Implicita (admin area)
│  └─ User debe estar logueado
│
├─ Autorización: En la vista
│  └─ Auth::guard()->user()->hasPermission('theme.activate')
│  └─ Auth::guard()->user()->hasPermission('theme.remove')
│
└─ Lógica: En controlador
   └─ abort_unless(config(...), 404)


POST /admin/theme/active
│
├─ Middleware: web, core, preventDemo
│  └─ preventDemo bloquea cambios en sitio demo
│
├─ Autenticación: Requerida
│  └─ User debe estar logueado
│
├─ Autorización: 'permission' => 'theme.index'
│  └─ Middleware AuthorizeRequest verifica permiso
│  └─ Si falla: 403 Forbidden
│
├─ CSRF Token: Automático en Laravel
│  └─ X-CSRF-TOKEN header o _token en POST
│
├─ Validación de Input:
│  ├─ $request->input('theme')  // String
│  ├─ Debe existir en servidor
│  └─ ThemeService::activate() valida antes de actualizar
│
└─ Lógica: En controlador y servicio
   ├─ abort_unless(config(...), 404)
   └─ ThemeService valida que tema existe
```

---

## 🗄️ Schema de Datos

### **Archivo theme.json (Ejemplo)**

```json
{
  "name": "Premium Theme",
  "description": "Un tema premium con funcionalidades avanzadas",
  "author": "Mercosan Team",
  "url": "https://mercosan.com",
  "version": "2.0.0",
  "presets": [],
  "support": ["homepage", "blog", "shop"]
}
```

### **Archivo config.php del Tema (Ejemplo)**

```php
return [
    'containerDir' => [
        'layout' => 'layouts',
        'asset' => 'assets',
        'partial' => 'partials',
        'view' => 'views',
    ],
    'inherit' => null,  // O nombre de tema padre
    'events' => [
        'before' => function($theme) { ... },
        'after' => function($theme) { ... },
    ],
];
```

### **Database - Table: settings**

```sql
┌────┬─────────────────┬──────────────────────────────────┐
│ id │ key             │ value                            │
├────┼─────────────────┼──────────────────────────────────┤
│ 1  │ theme           │ premium  ← Tema activo           │
│ 2  │ layout          │ default  ← Layout activo         │
│ 3  │ admin_logo      │ /images/logo.png                 │
│ 4  │ site_title      │ Mercosan                         │
│ ... │ ...             │ ...                              │
└────┴─────────────────┴──────────────────────────────────┘
```

---

## 📲 Vista Blade - Estructura HTML

```
list.blade.php
│
├─ @extends(BaseHelper::getAdminMasterLayoutTemplate())
│  └─ Usa layout: admin-master.blade.php
│
├─ @section('content')
│  │
│  └─ <div class="row row-cards mb-5">
│     │
│     ├─ @foreach ($themes as $key => $theme)
│     │  │
│     │  ├─ <div class="col-12 col-sm-6 col-lg-4">
│     │  │  │ <!-- Bootstrap 5 Grid -->
│     │  │  │ <!-- 1 col mobile, 2 tablet, 3 desktop -->
│     │  │  │
│     │  │  ├─ <x-core::card>
│     │  │  │  │
│     │  │  │  ├─ @if ($inherit = Arr::get($theme, 'inherit'))
│     │  │  │  │  │
│     │  │  │  │  └─ <div class="ribbon bg-red">
│     │  │  │  │     "Tema hijo de: {{ $themes[$inherit]['name'] }}"
│     │  │  │  │     </div>
│     │  │  │  │
│     │  │  │  ├─ <div class="card-img-top"
│     │  │  │  │      style="background-image: url('{{ Theme::getThemeScreenshot($key) }}')">
│     │  │  │  │  </div>
│     │  │  │  │  <!-- Screenshot del tema como background -->
│     │  │  │  │
│     │  │  │  ├─ <x-core::card.body>
│     │  │  │  │  │
│     │  │  │  │  ├─ <h4>{{ $theme['name'] }}</h4>
│     │  │  │  │  │  <!-- "Premium Theme", "Default Theme", etc -->
│     │  │  │  │  │
│     │  │  │  │  ├─ @if (!empty($theme['description']))
│     │  │  │  │  │  │
│     │  │  │  │  │  └─ <p>{{ $theme['description'] }}</p>
│     │  │  │  │  │     <!-- "Un tema premium con..." -->
│     │  │  │  │  │
│     │  │  │  │  ├─ <div class="row">
│     │  │  │  │  │  │
│     │  │  │  │  │  ├─ @if (!empty($theme['author']))
│     │  │  │  │  │  │  │
│     │  │  │  │  │  │  ├─ <div class="col">
│     │  │  │  │  │  │  │  │
│     │  │  │  │  │  │  │  ├─ "Autor: "
│     │  │  │  │  │  │  │  │
│     │  │  │  │  │  │  │  ├─ @if (!empty($theme['url']))
│     │  │  │  │  │  │  │  │  │
│     │  │  │  │  │  │  │  │  └─ <a href="{{ $theme['url'] }}">
│     │  │  │  │  │  │  │  │     {{ $theme['author'] }}</a>
│     │  │  │  │  │  │  │  │
│     │  │  │  │  │  │  │  └─ @else
│     │  │  │  │  │  │  │     {{ $theme['author'] }}
│     │  │  │  │  │  │  │
│     │  │  │  │  │  │  └─ </div>
│     │  │  │  │  │  │
│     │  │  │  │  │  ├─ @if (!empty($theme['version']))
│     │  │  │  │  │  │  │
│     │  │  │  │  │  │  └─ <div class="col">
│     │  │  │  │  │  │     "Versión: {{ $theme['version'] }}"
│     │  │  │  │  │  │     </div>
│     │  │  │  │  │  │
│     │  │  │  │  │  └─ </div>
│     │  │  │  │  │
│     │  │  │  │  └─ </x-core::card.body>
│     │  │  │  │
│     │  │  │  ├─ <x-core::card.footer>
│     │  │  │  │  │
│     │  │  │  │  └─ <div class="btn-list">
│     │  │  │  │     │
│     │  │  │  │     ├─ @if (setting('theme') && Theme::getThemeName() == $key)
│     │  │  │  │     │  │
│     │  │  │  │     │  └─ <!-- ES EL TEMA ACTIVO -->
│     │  │  │  │     │     <button disabled
│     │  │  │  │     │             color="info"
│     │  │  │  │     │             icon="ti ti-check">
│     │  │  │  │     │       [ACTIVADO]
│     │  │  │  │     │     </button>
│     │  │  │  │     │
│     │  │  │  │     ├─ @else
│     │  │  │  │     │  │
│     │  │  │  │     │  ├─ <!-- NO ES EL TEMA ACTIVO -->
│     │  │  │  │     │  │
│     │  │  │  │     │  ├─ @if (Auth::user()->hasPermission('theme.activate'))
│     │  │  │  │     │  │  │
│     │  │  │  │     │  │  └─ <button class="btn-trigger-active-theme"
│     │  │  │  │     │  │            color="primary"
│     │  │  │  │     │  │            data-url="/admin/theme/active"
│     │  │  │  │     │  │            data-theme="{{ $key }}">
│     │  │  │  │     │  │      [ACTIVAR]
│     │  │  │  │     │  │    </button>
│     │  │  │  │     │  │    <!-- Evento: clic → POST /admin/theme/active -->
│     │  │  │  │     │  │
│     │  │  │  │     │  ├─ @if (Auth::user()->hasPermission('theme.remove'))
│     │  │  │  │     │  │  │
│     │  │  │  │     │  │  └─ <button class="btn-trigger-remove-theme"
│     │  │  │  │     │  │            data-url="/admin/theme/remove"
│     │  │  │  │     │  │            data-theme="{{ $key }}">
│     │  │  │  │     │  │      [ELIMINAR]
│     │  │  │  │     │  │    </button>
│     │  │  │  │     │  │    <!-- Evento: clic → Modal confirmación -->
│     │  │  │  │     │  │
│     │  │  │  │     │  └─ @endif
│     │  │  │  │     │
│     │  │  │  │     └─ </div>
│     │  │  │  │
│     │  │  │  └─ </x-core::card>
│     │  │  │
│     │  │  └─ </div>
│     │  │
│     │  └─ @endforeach
│     │
│     └─ </div>
│
├─ @push('footer')
│  │
│  └─ <x-core::modal.action id="remove-theme-modal">
│     <!-- Modal de confirmación -->
│     <h2>¿Eliminar tema?</h2>
│     <p>Esta acción no se puede deshacer</p>
│     <button id="confirm-remove-theme-button">CONFIRMAR</button>
│     </x-core::modal.action>
│
└─ @endpush
```

---

## 🔌 Puntos de Extensión

```
Theme Module → Extensibility Points
│
├─ Service Providers (hooks/events)
│  │
│  ├─ ThemeServiceProvider
│  │  ├─ Registra facades
│  │  ├─ Registra singleton Manager
│  │  └─ Registra servicios
│  │
│  ├─ RouteServiceProvider
│  │  └─ Registra rutas
│  │
│  ├─ HookServiceProvider
│  │  ├─ Hooks: do_action('theme.activated', $themeName)
│  │  ├─ Hooks: do_action('theme.removed', $themeName)
│  │  └─ Filters: apply_filters('theme_activated', $result)
│  │
│  └─ EventServiceProvider
│     ├─ Event: ThemeChanged
│     ├─ Event: ThemeActivated
│     └─ Event: ThemeRemoved
│
├─ Filters & Hooks (WordPress-style)
│  │
│  ├─ do_action(RENDERING_THEME_OPTIONS_PAGE)
│  ├─ apply_filters('theme.activate.theme.{name}', $result)
│  └─ apply_filters('theme_body_attributes', $html)
│
├─ Theme Config Override
│  │
│  ├─ themes/{tema}/config.php
│  │  ├─ events.before
│  │  ├─ events.after
│  │  ├─ containerDir
│  │  └─ inherit
│  │
│  └─ Listeners en HookServiceProvider
│     └─ Se ejecutan en orden
│
└─ Database Triggers
   │
   ├─ AfterActivate:
   │  └─ Cache::clear()
   │  └─ Event::dispatch(ThemeChanged)
   │
   └─ AfterRemove:
      └─ File::deleteDirectory(theme_path($theme))
```

---

## ⚙️ Configuración del Sistema

```
config/general.php
│
├─ themeDefault
│  └─ 'default' - Tema por defecto si no hay configurado
│
├─ layoutDefault
│  └─ 'default' - Layout por defecto
│
├─ themeDir
│  └─ 'themes' - Carpeta donde buscar temas
│
├─ containerDir
│  │
│  ├─ layout → 'layouts' - Subcarpeta de layouts Blade
│  ├─ asset → '' - Subcarpeta de assets (CSS, JS, img)
│  ├─ partial → 'partials' - Subcarpeta de partials
│  └─ view → 'views' - Subcarpeta de vistas
│
├─ enable_custom_js
│  └─ true/false - Permitir CSS/JS personalizado
│
├─ enable_custom_html
│  └─ true/false - Permitir HTML personalizado
│
├─ enable_robots_txt_editor
│  └─ true/false - Editor de robots.txt
│
└─ display_theme_manager_in_admin_panel
   └─ true/false - Mostrar /admin/theme/all
```

---

## 🎯 Casos de Éxito vs Error

```
ACTIVAR TEMA
│
├─ ÉXITO ✓
│  ├─ Tema existe
│  ├─ Tiene archivo theme.json válido
│  ├─ No hay dependencias rotas
│  ├─ BD se actualiza
│  ├─ Caché se limpia
│  └─ Notificación verde + recarga página
│
└─ ERROR ✗
   ├─ Tema no existe
   │  └─ Mensaje: "El tema no existe"
   │
   ├─ theme.json inválido
   │  └─ Mensaje: "Configuración del tema inválida"
   │
   ├─ Tema padre no existe (si hereda)
   │  └─ Mensaje: "El tema padre no está disponible"
   │
   ├─ Sin permiso
   │  └─ HTTP 403 Forbidden
   │
   └─ Sitio en modo demo
      └─ Middleware preventDemo bloquea


ELIMINAR TEMA
│
├─ ÉXITO ✓
│  ├─ Modal de confirmación
│  ├─ Tema existe
│  ├─ Tema no es el activo
│  ├─ Carpeta se elimina
│  └─ Notificación verde + recarga página
│
└─ ERROR ✗
   ├─ Tema no existe
   │  └─ Mensaje: "El tema no existe"
   │
   ├─ Es el tema activo
   │  └─ Mensaje: "No se puede eliminar el tema activo"
   │
   ├─ Sin permiso
   │  └─ HTTP 403 Forbidden
   │
   ├─ Otros temas lo heredan
   │  └─ Opción: Avisar o impedir
   │
   └─ Carpeta no se puede borrar
      └─ Mensaje: "No se pueden eliminar archivos"
```

---

## 📊 Diagrama de Secuencia (PlantUML-style)

```
User              Browser           JavaScript       Server            Database
  │                 │                  │               │                 │
  ├──GET /admin/──→│                  │               │                 │
  │ theme/all      │                  │               │                 │
  │                 ├────────GET────────────────────→│                 │
  │                 │                  │               │                 │
  │                 │                  │               ├────SELECT───────→│
  │                 │                  │               │ (settings table) │
  │                 │                  │               │←────rows─────────┤
  │                 │                  │               │                 │
  │                 │                  │               ├─ getThemes()    │
  │                 │                  │               │ ├─ scan themes/ │
  │                 │                  │               │ ├─ read JSON    │
  │                 │                  │               │ └─ build array  │
  │                 │                  │               │                 │
  │                 │←────────HTML─────────────────────┤                 │
  │←────HTML page──┤                  │               │                 │
  │ (3 tarjetas)   │                  │               │                 │
  │                 │                  │               │                 │
  │ clicks         │                  │               │                 │
  │ [ACTIVAR]      │                  │               │                 │
  │                 ├─────────────────→│               │                 │
  │                 │                  ├─ POST /admin/theme/active       │
  │                 │                  │   theme=premium                 │
  │                 │                  ├──────────────→│                 │
  │                 │                  │               ├─validate        │
  │                 │                  │               │ ├─exists?       │
  │                 │                  │               │ └─permission?   │
  │                 │                  │               │                 │
  │                 │                  │               ├──UPDATE ────────→│
  │                 │                  │               │ theme='premium'  │
  │                 │                  │               │←───OK────────────┤
  │                 │                  │               │                 │
  │                 │                  │               ├─Cache::clear()  │
  │                 │                  │               │                 │
  │                 │                  │←─JSON response│                 │
  │                 │←─JSON response────│               │                 │
  │                 │                  │               │                 │
  │                 ├─show notification│               │                 │
  │ ✓ Success       ├─window.reload    │               │                 │
  │                 │                  │               │                 │
  │                 ├────GET /admin/────────────────→│                 │
  │                 │ theme/all        │               │                 │
  │                 │ (reload)         │               ├─ select themes  │
  │                 │                  │               │←─rows───────────┤
  │                 │←───HTML updated──┤               │                 │
  │                 │ (Premium=ACTIVE) │               │                 │
  │                 │                  │               │                 │
  └────────────────┴──────────────────┴───────────────┴─────────────────┘
```

