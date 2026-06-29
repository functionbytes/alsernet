# Facade Module:: y Metodos

## Importar
```php
use Nwidart\Modules\Facades\Module;
```

## Metodos del Facade (repositorio)

### Consultar modulos
| Metodo | Retorno | Descripcion |
|--------|---------|-------------|
| `Module::all()` | Collection | Todos los modulos |
| `Module::allEnabled()` | Collection | Solo habilitados |
| `Module::allDisabled()` | Collection | Solo deshabilitados |
| `Module::getOrdered()` | Collection | Ordenados por prioridad |
| `Module::getByStatus(1)` | Collection | Por estado (1=activo, 0=inactivo) |
| `Module::count()` | int | Total de modulos |
| `Module::toCollection()` | Collection | Como Laravel Collection |
| `Module::collections()` | Collection | Habilitados como Collection |

### Buscar modulos
| Metodo | Retorno | Descripcion |
|--------|---------|-------------|
| `Module::find('Blog')` | Module\|null | Buscar por nombre |
| `Module::get('Blog')` | Module\|null | Alias de find() |
| `Module::findOrFail('Blog')` | Module | Lanza excepcion si no existe |
| `Module::has('blog')` | bool | Verificar existencia |

### Rutas y paths
| Metodo | Retorno | Descripcion |
|--------|---------|-------------|
| `Module::getPath()` | string | Ruta base de modulos |
| `Module::getModulePath('Blog')` | string | Ruta del modulo especifico |
| `Module::assetPath('Blog')` | string | Ruta de assets |
| `Module::asset('blog:img/logo.png')` | string | URL de asset |
| `Module::getScanPaths()` | array | Rutas de escaneo |
| `Module::getAssetsPath()` | string | Ruta general de assets |

### Gestion
| Metodo | Descripcion |
|--------|-------------|
| `Module::register()` | Registrar modulos |
| `Module::boot()` | Inicializar modulos |
| `Module::install('nwidart/hello')` | Instalar modulo externo |
| `Module::update('hello')` | Actualizar dependencias |

### Cache y configuracion
| Metodo | Descripcion |
|--------|-------------|
| `Module::getCached()` | Modulos en cache |
| `Module::scan()` | Escanear disponibles |
| `Module::config('key')` | Valor de config del paquete |
| `Module::getUsedNow()` | Modulo activo en CLI |
| `Module::setUsed('Blog')` | Establecer modulo CLI |

### Extensibilidad
| Metodo | Descripcion |
|--------|-------------|
| `Module::macro('nombre', fn)` | Agregar macro |
| `Module::getRequirements('Blog')` | Obtener dependencias |

## Metodos de instancia Module

Obtenidos via `Module::find('Blog')`:

| Metodo | Retorno | Descripcion |
|--------|---------|-------------|
| `->getName()` | string | Nombre (PascalCase) |
| `->getLowerName()` | string | Nombre lowercase |
| `->getStudlyName()` | string | Nombre StudlyCase |
| `->getPath()` | string | Ruta del modulo |
| `->getExtraPath('config')` | string | Sub-ruta dentro del modulo |
| `->enable()` | void | Habilitar modulo |
| `->disable()` | void | Deshabilitar modulo |
| `->delete()` | void | Eliminar modulo |
| `->isEnabled()` | bool | Esta habilitado? |
| `->isDisabled()` | bool | Esta deshabilitado? |
| `->getRequires()` | array | Dependencias del modulo |

## Helper function

```php
module_path('Blog');                    // Ruta del modulo
module_path('Blog', 'routes/web.php'); // Ruta a archivo dentro del modulo
```

## Namespaces automaticos

Al crear un modulo se registran namespaces para:

### Vistas
```php
view('blog::index')
view('blog::partials.sidebar')
```

### Traducciones
```php
Lang::get('blog::messages.welcome')
@trans('blog::group.name')
```

### Configuracion
```php
config('blog.name')
config('blog.settings.option')
```
