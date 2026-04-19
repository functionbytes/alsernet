# Plantilla Wowy - inoqualab

Plantilla premium **Wowy** - Sistema ecommerce multipropósito con soporte completo para productos, categorías, carritos y checkout.

## 📋 Descripción

**Wowy** es una plantilla de e-commerce profesional y versátil que proporciona:

- ✅ **13 layouts diferentes** para múltiples tipos de contenido
- ✅ **21 partials reutilizables** para componentes comunes
- ✅ **Soporte ecommerce completo** - productos, categorías, carrito, checkout
- ✅ **Diseño responsive** - funciona en todos los dispositivos
- ✅ **Soporte RTL** - para idiomas de derecha a izquierda
- ✅ **Widgets avanzados** - carrusel de productos, menú mega, testimonios, etc.
- ✅ **Assets completos** - CSS, JS, fuentes personalizadas
- ✅ **Múltiples idiomas** - EN y ES incluidos

## 🎯 Layouts Disponibles

```
layouts/
├── default.blade.php              - Diseño por defecto
├── homepage.blade.php             - Página de inicio
├── full-width.blade.php           - Ancho completo (sin sidebars)
├── blog-left-sidebar.blade.php    - Blog con sidebar izquierdo
├── blog-right-sidebar.blade.php   - Blog con sidebar derecho
├── blog-full-width.blade.php      - Blog a ancho completo
├── product-left-sidebar.blade.php - Página de producto con sidebar izquierdo
├── product-right-sidebar.blade.php- Página de producto con sidebar derecho
└── product-full-width.blade.php   - Página de producto a ancho completo
```

## 🧩 Componentes (Partials)

```
partials/
├── header/                - Encabezado (navegación, búsqueda, carrito)
├── footer/                - Pie de página
├── sidebar/               - Sidebars (filtros, widgets)
├── product-cards/        - Tarjetas de productos
├── breadcrumbs/          - Navegación de migas de pan
├── pagination/           - Paginación
├── forms/                - Formularios (login, registro, búsqueda)
├── modals/               - Modales (quickview, carrito)
└── ...                    - Y más
```

## 🛠 Configuración

Dos archivos de configuración disponibles:

### 1. `theme.json` (Original Botble)
```json
{
    "name": "Wowy",
    "namespace": "Theme\\Wowy\\",
    "description": "Wowy - Laravel Multipurpose eCommerce script",
    "author": "Botble Technologies",
    "required_plugins": []
}
```

### 2. `template.json` (inoqualab)
```json
{
    "name": "Wowy - Ecommerce Premium",
    "namespace": "Template\\Wowy\\",
    "description": "Plantilla premium Wowy...",
    "author": "Botble Technologies / Alsernet",
    "version": "1.0.0",
    "inherit": null,
    "required_plugins": ["ecommerce"]
}
```

### 3. `config-inoqualab.php` (Configuración inoqualab)
Define opciones, assets, widgets y características disponibles.

## 📦 Assets Incluidos

### CSS (en `public/css/`)
- `normalize.css` - Normalización de estilos
- `style.css` - Estilos principales de Wowy
- `rtl.css` - Estilos para RTL
- Plugins: animate, slick, bootstrap, fontawesome, wowy-font

### JavaScript (en `public/js/`)
- Bootstrap bundle
- jQuery y jQuery migrate
- Slick carousel
- jQuery countdown, syotimer, vticker
- Wow.js para animaciones
- Waypoints.js para scroll effects
- Custom scripts: main.js, backend.js

## 🎨 Funciones y Utilidades

```
functions/
├── functions.php                  - Funciones generales
├── shortcodes.php                 - Sistema de shortcodes
├── theme-options.php              - Opciones de tema
├── theme-icons-field.php          - Campo de selección de iconos
├── menu-icon-image.php            - Iconos en menús
├── facebook-integration.php       - Integración Facebook
├── facebook-integration-extra.php - Extras Facebook
└── simple-slider-responsive-sizes.php - Slider responsivo
```

## 👁 Vistas Especializadas

```
views/
├── index.blade.php                - Página de inicio
├── tag.blade.php                  - Página de etiquetas
├── 404.blade.php                  - Página de error 404
└── ecommerce/
    ├── brand.blade.php            - Página de marca
    ├── customers/
    │   ├── edit-account.blade.php - Editar cuenta
    │   ├── register.blade.php     - Registro
    │   └── passwords/
    │       ├── email.blade.php    - Recuperar contraseña
    │       └── reset.blade.php    - Resetear contraseña
    ├── product/
    │   ├── index.blade.php        - Listado de productos
    │   └── detail.blade.php       - Detalle de producto
    ├── wishlist/
    │   └── index.blade.php        - Wishlist del usuario
    └── ... más
```

## 🎯 Widgets Disponibles

- 📦 **Carrusel de Productos** - Productos destacados en carrusel
- 📂 **Menú de Categorías** - Menú mega con categorías
- ⭐ **Productos Destacados** - Productos featured
- 🏷️ **Productos en Oferta** - Productos en descuento
- 📧 **Suscripción Newsletter** - Formulario de suscripción
- 💬 **Testimonios** - Reviews de clientes
- 🏷️ **Logos de Marcas** - Marcas colaboradoras
- 🎁 **Ofertas Especiales** - Banners de ofertas

## ✨ Características

- ✅ Diseño responsivo (mobile, tablet, desktop)
- ✅ Soporte completo para RTL (árabe, hebreo)
- ✅ Menú mega con iconos
- ✅ Búsqueda avanzada de productos
- ✅ Filtrado por AJAX
- ✅ Vista rápida de productos (modal)
- ✅ Comparador de productos
- ✅ Wishlist
- ✅ Carrito de compras
- ✅ Checkout seguro
- ✅ Animaciones suaves
- ✅ Efectos de scroll

## 📚 Idiomas Soportados

- 🇬🇧 English (en.json)
- 🇪🇸 Español (es.json)

## 🚀 Activación en inoqualab

1. Desde el admin:
   ```
   /admin/templates → Grid de plantillas
   ```

2. Haz clic en **[ACTIVAR]** en la tarjeta de Wowy

3. Todos los sitios usarán los layouts, partials y assets de Wowy

## 🔗 Rutas y API

```
routes/
└── web.php  - Rutas específicas de la plantilla
```

## 📂 Estructura Completa

```
platform/themes/wowy/
├── template.json          ← inoqualab template metadata
├── theme.json            ← Botble theme metadata
├── config.php            ← Config original Botble
├── config-inoqualab.php  ← Config para inoqualab
├── screenshot.png        ← Preview de la plantilla
├── assets/               ← Assets compilados
├── public/               ← CSS, JS, imágenes
├── layouts/              ← 13 layouts Blade
├── partials/             ← 21+ partials reutilizables
├── views/                ← Vistas especializadas
├── functions/            ← Funciones PHP
├── widgets/              ← Widgets/componentes
├── lang/                 ← Traducciones (EN, ES)
├── routes/               ← Rutas específicas
├── src/                  ← Código fuente
├── webpack.mix.js        ← Build configuration
└── README-INOQUALAB.md   ← Este archivo
```

## ⚠️ Requisitos

- Laravel 12+
- PHP 8.4+
- Módulo **Ecommerce** activo
- Bootstrap 5.3+

## 🎨 Personalización

Para personalizar Wowy:

1. **Editar estilos**: Modifica archivos en `public/css/`
2. **Editar layouts**: Modifica archivos en `layouts/`
3. **Editar partials**: Modifica archivos en `partials/`
4. **Agregar idiomas**: Agrega archivos en `lang/`
5. **Crear widgets**: Agrega widgets en `widgets/`

## 📝 Notas Importantes

- ✅ **tema.json original** mantiene compatibilidad con Botble
- ✅ **template.json** proporciona metadata para inoqualab
- ✅ **config-inoqualab.php** documenta la configuración específica
- ✅ Todos los assets están incluidos en `public/`
- ✅ Soporta versionado automático como cualquier template

## 🤝 Soporte

Para reportar problemas o solicitar mejoras:
- Contacta al equipo de Alsernet
- O consulta la documentación de Wowy/Botble

---

**Última actualización**: Febrero 2026
**Creado por**: Botble Technologies
**Adaptado para inoqualab por**: Alsernet
