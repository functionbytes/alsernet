# Convenciones obligatorias del proyecto Alsernet

> Reglas que TODOS los templates generados deben seguir.
> Ver detalle de Riode en `/Users/developerts/Desktop/Plantillas/riode-frontend-skill/references/conventions.md`

## 1. CSS — NO inline styles

### ❌ PROHIBIDO
```html
<div style="background-color: #d26e4b; padding: 20px;">
<img style="width: 100%;">
```

### ✅ PERMITIDO
```html
<!-- Utility classes -->
<div class="bg-primary-brand p-4">

<!-- Data attribute + CSS custom property -->
<div data-bg-color="#d26e4b" class="p-4">

<!-- Clase semántica del componente -->
<div class="banner banner--primary p-4">
```

### Excepciones permitidas
- Posicionamiento dinámico (`top: X%; left: Y%;` en `[hotspot-pin]`)
- Background-image con URL dinámica → usar `data-bg-image` + JS minimal:
  ```js
  $('[data-bg-image]').each(function() {
      $(this).css('background-image', `url('${$(this).data('bg-image')}')`);
  });
  ```

## 2. Iconos — Font Awesome 6 ONLY

### ✅ PERMITIDO
```html
<i class="fas fa-cart"></i>
<i class="far fa-heart"></i>
<i class="fab fa-facebook-f"></i>
```

### ❌ PROHIBIDO
```html
<!-- Tabler Icons NO cargados -->
<i class="ti ti-shopping-cart"></i>

<!-- d-icon-* (Riode propietario) NO cargado -->
<i class="d-icon-bag"></i>
```

### Mapping universal Riode/otros → FA6

```php
$d_icon_to_fa6 = [
    'd-icon-bag' => 'fas fa-shopping-bag',
    'd-icon-cart' => 'fas fa-shopping-cart',
    'd-icon-heart' => 'far fa-heart',
    'd-icon-heart-full' => 'fas fa-heart',
    'd-icon-search' => 'fas fa-magnifying-glass',
    'd-icon-user' => 'far fa-user',
    'd-icon-menu' => 'fas fa-bars',
    'd-icon-close' => 'fas fa-xmark',
    'd-icon-arrow-right' => 'fas fa-arrow-right',
    'd-icon-arrow-left' => 'fas fa-arrow-left',
    'd-icon-plus' => 'fas fa-plus',
    'd-icon-minus' => 'fas fa-minus',
    'd-icon-check' => 'fas fa-check',
    'd-icon-star' => 'fas fa-star',
    'd-icon-eye' => 'far fa-eye',
    'd-icon-phone' => 'fas fa-phone',
    'd-icon-envelope' => 'fas fa-envelope',
    'd-icon-pin' => 'fas fa-location-dot',
    'd-icon-clock' => 'far fa-clock',
    'd-icon-shield' => 'fas fa-shield-halved',
    'd-icon-truck' => 'fas fa-truck',
    'd-icon-recycle' => 'fas fa-recycle',
    'd-icon-headphone' => 'fas fa-headset',
    'd-icon-play' => 'fas fa-play',
    'd-icon-home' => 'fas fa-house',
    'd-icon-tag' => 'fas fa-tag',
    'd-icon-share' => 'fas fa-share-nodes',
    'd-icon-sliders' => 'fas fa-sliders',
    // ... ver lista completa en /riode-analisis/10-css-modales-fonts.md
];
```

## 3. JavaScript — jQuery + Bootstrap 5.3 nativo

### ✅ PERMITIDO
- jQuery 3.x (ya cargado en proyecto)
- Bootstrap 5.3 nativo (`data-bs-toggle`, `new bootstrap.Modal(...)`)
- Plain JS / IntersectionObserver / ResizeObserver
- Event delegation: `$(document).on('click', '.selector', handler)`
- AJAX con CSRF token

### ❌ PROHIBIDO
- Livewire
- Inertia.js
- React, Vue, Svelte (NO SPAs)
- Alpine.js (innecesario, jQuery ya está)

### Patrones obligatorios

```js
// ✅ Event delegation (para contenido dinámico)
$(document).on('click', '.btn-action', function(e) {
    e.preventDefault();
    const id = $(this).data('id');
    // ...
});

// ✅ AJAX con CSRF
$.ajax({
    url: '/api/endpoint',
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    data: { id },
    success: res => toastr.success(res.message),
    error: xhr => {
        if (xhr.status === 422) {
            $.each(xhr.responseJSON.errors, (field, messages) => toastr.error(messages[0]));
        } else {
            toastr.error('Error inesperado');
        }
    }
});

// ✅ Bootstrap 5 modal
const modal = new bootstrap.Modal(document.getElementById('myModal'));
modal.show();

// ✅ IntersectionObserver para animaciones
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animated', 'fadeIn');
            observer.unobserve(entry.target);
        }
    });
});
```

## 4. Color primario

✅ **SIEMPRE**: `--color-primary: #b10100` (verde Alsernet)
❌ **NUNCA**: usar el color primario del template original (Riode usa `#26c` o `#d26e4b`)

En `tokens.css` del template:

```css
:root {
    /* Brand Alsernet — sustituye color del template original */
    --color-primary: #b10100;
    --color-primary-hover: #7da010;
    --color-primary-light: #d3e8a8;
    
    /* ... otros tokens del template original ... */
}
```

## 5. Performance — Lighthouse-friendly

### Imágenes

```html
<!-- ✅ SIEMPRE -->
<img src="image.jpg" 
     alt="Descripción" 
     width="600" 
     height="400" 
     loading="lazy" 
     decoding="async">

<!-- Para hero (above-the-fold) -->
<img src="hero.jpg" alt="..." width="1920" height="1080" fetchpriority="high">
```

### CSS / JS bajo demanda

```blade
@once
    @push('scripts')
        <script src="..."></script>
    @endpush
@endonce
```

### N+1 Queries

```php
// ❌ N+1
$products = Product::limit(8)->get();

// ✅ Eager loading
$products = Product::with(['category', 'media', 'brand'])
    ->withCount('reviews')
    ->limit(8)
    ->get();
```

## 6. Accesibilidad

```html
<!-- ✅ aria-label en botones icon-only -->
<button class="btn-icon" aria-label="Cerrar"><i class="fas fa-times"></i></button>

<!-- ✅ role + aria-* donde aplique -->
<div class="alert" role="alert" aria-live="polite">...</div>
<div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">

<!-- ✅ alt en todas las imágenes -->
<img src="..." alt="Descripción significativa" />

<!-- ✅ Skip a contenido principal -->
<a href="#main" class="visually-hidden-focusable">Saltar al contenido</a>
```

## 7. Multi-idioma

```php
// modules/Page/resources/lang/es/shortcodes.php
return [
    'countdown' => [
        'days' => 'Días',
        'hours' => 'Horas',
        // ...
    ],
    'cta' => ['default_button' => 'Saber más'],
    'icon_box' => ['free_shipping' => 'Envío gratis'],
];
```

```blade
<button>{{ __('shortcodes.button.cta_default') }}</button>
<p>{{ __('shortcodes.cta.description', ['count' => $count]) }}</p>
```

Convención: traducciones en `modules/Template/Templates/{Name}/Resources/lang/{locale}/shortcodes.php` o usar el global del módulo Page.

## 8. Seguridad — XSS protection

### Atributos strings (potencialmente unsafe)

```php
// ✅ Escape obligatorio
$attr = htmlspecialchars($attrs['user-input'] ?? '');
echo "<a href=\"$attr\">link</a>";

// ✅ Validar enum
$validStyles = ['default', 'primary', 'outline'];
$style = in_array($attrs['style'] ?? '', $validStyles, true) ? $attrs['style'] : 'default';
```

### Contenido entre tags ([alert]...[/alert])

El `$content` se pasa SIN escape porque admite HTML y shortcodes anidados (estilo WordPress). Por tanto solo procesar shortcodes sobre contenido CONFIABLE de administradores.

```blade
{{-- ❌ NUNCA: --}}
{{-- {!! $userInput !!} --}}

{{-- ✅ Para contenido admin: --}}
{!! $content !!}

{{-- ✅ Para texto de usuario: --}}
{{ $userInput }}
```

## 9. Tests obligatorios

Cada shortcode debe tener al menos:

- 1 test happy path (con todos los atributos válidos)
- 1 test edge case (atributo requerido faltante → return empty)
- 1 test enum validation (style inválido → fallback a default)

Patrón:

```php
/** @test */
public function test_{shortcode}_renders_with_required_attrs(): void
{
    $result = shortcode('[shortcode title="Test"][/shortcode]');
    $this->assertStringContainsString('expected-class', $result);
}

/** @test */
public function test_{shortcode}_returns_empty_when_required_attr_missing(): void
{
    $result = shortcode('[shortcode][/shortcode]');
    $this->assertEmpty(trim($result));
}

/** @test */
public function test_{shortcode}_validates_style_enum(): void
{
    $result = shortcode('[shortcode title="Test" style="invalid"][/shortcode]');
    $this->assertStringContainsString('shortcode-default', $result);
}
```

## 10. Permisos — Spatie permission

```php
class ProductDetailShortcode
{
    protected function authorize(): bool
    {
        return auth()->user()?->can('shop.products.preview') ?? false;
    }
}
```

Convención de permisos:
- `shop.products.preview` — preview de drafts
- `page.shortcodes.use` — usar shortcodes en VE
- `template.activate` — cambiar template activo

## 11. Checklist pre-merge

Antes de mergear cualquier template:

- [ ] Sin `style=""` inline (excepto excepciones documentadas)
- [ ] Iconos: solo FA6
- [ ] JS: jQuery / Bootstrap 5.3 / vanilla
- [ ] Color primario `#b10100` en tokens.css
- [ ] Atributos validados (enum + fallback)
- [ ] `loading="lazy"` + dimensiones en `<img>`
- [ ] Eager loading en queries DB
- [ ] `@once + @push` para JS de plugins
- [ ] Strings UI con `__()`
- [ ] Tests Feature happy + edge cases
- [ ] `vendor/bin/pint --dirty` antes de commit
- [ ] `composer dump-autoload` ejecutado
- [ ] `php artisan optimize:clear` ejecutado
- [ ] Seeder ejecutado y template activado
- [ ] `php artisan shortcode:list` muestra los nuevos
- [ ] README.md del template generado

## 12. Plugin Replacements (Plan B)

| Plugin Riode/template | Alternativa moderna recomendada |
|------------------------|----------------------------------|
| owl-carousel 2 | **Swiper 11** |
| magnific-popup | **GLightbox** o **Bootstrap modal nativo** |
| sticky.min.js | **CSS `position: sticky`** |
| skrollr (deprecado) | **IntersectionObserver** + CSS transforms |
| isotope | **CSS Grid + grid-auto-flow: dense** |
| jquery.gmap (con API key) | **iframe simple sin API key** |
| d-icon-* font | **Font Awesome 6** (ya cargado) |
| jquery.countdown | Mantener (ligero ~5KB) o vanilla JS |
| jquery.count-to | **IntersectionObserver vanilla** |
| jquery.floating | Vanilla JS (~30 líneas) |

Eliminables sin reemplazo: codemirror, popup-code, jquery.plugin (~252 KB ahorrados).
