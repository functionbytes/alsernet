# Shortcode Patterns — Cómo escribir cada categoría

> Patrones de implementación para clases PHP + Blade views.

## Patrón base de una clase

Sigue este esqueleto EXACTO:

```php
<?php

namespace Modules\Template\Templates\{Name}\Shortcodes;

use Modules\Shortcode\Compiler\ShortcodeCompiler;

class {Name}{Category}Shortcodes
{
    public function __construct(private readonly ShortcodeCompiler $compiler) {}

    public function registerAll(): void
    {
        $this->register{Shortcode1}();
        $this->register{Shortcode2}();
        $this->register{Shortcode3}();
    }

    // -------------------------------------------------------------------------
    // [shortcode-name attr="value"]content[/shortcode-name]
    // -------------------------------------------------------------------------
    protected function register{Shortcode1}(): void
    {
        $this->compiler->register('shortcode-name', function (array $attrs, string $content): string {
            return view('{slug}::shortcodes.shortcode-name', compact('attrs', 'content'))->render();
        }, [
            'description' => 'Descripción breve del shortcode',
            'example'     => '[shortcode-name attr="value"][/shortcode-name]',
            'attributes'  => [
                'attr1' => 'Descripción del atributo 1',
                'attr2' => 'Descripción del atributo 2',
            ],
        ]);
    }
}
```

### Reglas

1. Una sola clase por categoría
2. Método `registerAll()` público — llama a todos los `register{Shortcode}()` privados
3. Cada `register{Shortcode}()` registra UN shortcode
4. Usar `view('{slug}::shortcodes.{name}', ...)` con namespace del template
5. Closure recibe `(array $attrs, string $content)` y retorna `string`
6. Metadata obligatorio: `description`, `example`, `attributes`

## Patrón base de una Blade view

```blade
@php
    // 1. Extraer atributos con defaults seguros
    $style = $attrs['style'] ?? 'default';
    $class = $attrs['class'] ?? '';
    
    // 2. Validar enums (whitelist)
    $validStyles = ['default', 'primary', 'outline', 'ghost'];
    $style = in_array($style, $validStyles, true) ? $style : 'default';
    
    // 3. Coerciones de tipo (string → bool/int/array)
    $dismissible = filter_var($attrs['dismissible'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $cols = max(1, min(12, (int) ($attrs['cols'] ?? 4)));
    
    // 4. JSON parsing con fallback
    $items = is_string($attrs['items'] ?? '[]') 
        ? (json_decode($attrs['items'] ?? '[]', true) ?? [])
        : (array) ($attrs['items'] ?? []);
    
    // 5. Validar contenido obligatorio (return early si falta)
    if (empty($attrs['title'] ?? null)) {
        return '';  // Skip render si falta atributo crítico
    }
@endphp

<div class="{component-class} component-{{ $style }} {{ $class }}"
     @if($dataAttrs ?? false) data-attribute="{{ $dataAttrs }}" @endif>
    
    {{-- Contenido seguro contra XSS --}}
    <h3>{{ $attrs['title'] }}</h3>
    
    {{-- Contenido con HTML permitido (admin trusted) --}}
    <div>{!! $content !!}</div>
    
    {{-- Iconos FA6 --}}
    @if(!empty($attrs['icon']))
        <i class="fas {{ htmlspecialchars($attrs['icon']) }}"></i>
    @endif
    
    {{-- Imagenes con loading lazy + dimensiones --}}
    @if(!empty($attrs['image']))
        <img src="{{ htmlspecialchars($attrs['image']) }}" 
             alt="{{ htmlspecialchars($attrs['title']) }}" 
             width="{{ $attrs['image-width'] ?? 600 }}" 
             height="{{ $attrs['image-height'] ?? 400 }}"
             loading="lazy">
    @endif
    
    {{-- Multi-idioma --}}
    <button class="btn btn-primary">{{ __('shortcode::messages.{shortcode}.button_text') }}</button>
</div>

{{-- JS plugins via @once + @push (cargan UNA vez aunque shortcode aparezca N veces) --}}
@once
    @push('scripts')
        <script>
            // JS init aquí
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.{component-class}').forEach(el => {
                    // initialization
                });
            });
        </script>
    @endpush
    
    @push('styles')
        <style>
            .{component-class} {
                /* CSS específico que no está en tokens.css */
            }
        </style>
    @endpush
@endonce
```

## Patrón de tests Feature

```php
<?php

namespace Modules\Template\Templates\{Name}\Tests\Feature;

use Tests\TestCase;

class {Name}{Category}ShortcodesTest extends TestCase
{
    /** @test */
    public function test_{shortcode}_renders_with_required_attrs(): void
    {
        $result = shortcode('[{shortcode} title="Test"][/{shortcode}]');
        
        $this->assertStringContainsString('component-class', $result);
        $this->assertStringContainsString('Test', $result);
    }

    /** @test */
    public function test_{shortcode}_uses_default_style_when_not_provided(): void
    {
        $result = shortcode('[{shortcode} title="Test"][/{shortcode}]');
        
        $this->assertStringContainsString('component-default', $result);
    }

    /** @test */
    public function test_{shortcode}_validates_style_enum(): void
    {
        // Estilo inválido → fallback a default
        $result = shortcode('[{shortcode} title="Test" style="invalid"][/{shortcode}]');
        
        $this->assertStringContainsString('component-default', $result);
        $this->assertStringNotContainsString('component-invalid', $result);
    }

    /** @test */
    public function test_{shortcode}_returns_empty_when_required_attr_missing(): void
    {
        // Sin title (atributo requerido)
        $result = shortcode('[{shortcode}][/{shortcode}]');
        
        $this->assertEmpty(trim($result));
    }
}
```

## Patrones específicos por tipo de shortcode

### 1. Shortcode "wrapper" (sin children, contenido simple)

Ejemplo: `[animate]content[/animate]`

```php
protected function registerAnimate(): void
{
    $this->compiler->register('animate', function (array $attrs, string $content): string {
        if (trim($content) === '') return '';  // No render si vacío
        
        return view('{slug}::shortcodes.animate', compact('attrs', 'content'))->render();
    }, [...]);
}
```

### 2. Shortcode "parent + children"

Ejemplo: `[tabs]` con `[tab title="..."]content[/tab]` dentro

```php
protected function registerTabs(): void
{
    $this->compiler->register('tabs', function (array $attrs, string $content): string {
        return view('{slug}::shortcodes.tabs', compact('attrs', 'content'))->render();
    }, [...]);
}

protected function registerTab(): void
{
    $this->compiler->register('tab', function (array $attrs, string $content): string {
        return view('{slug}::shortcodes.tab', compact('attrs', 'content'))->render();
    }, [...]);
}
```

En la view del parent, el `$content` ya viene compilado con los children renderizados:

```blade
{{-- tabs.blade.php --}}
<div class="tabs">
    <ul class="nav nav-tabs">{!! /* TODO: extraer titles de los <div class="tab-pane"> */ !!}</ul>
    <div class="tab-content">{!! $content !!}</div>
</div>
```

### 3. Shortcode self-closing (sin contenido)

Ejemplo: `[breadcrumb /]`, `[social-links /]`

```php
protected function registerBreadcrumb(): void
{
    $this->compiler->register('breadcrumb', function (array $attrs, string $content = ''): string {
        return view('{slug}::shortcodes.breadcrumb', compact('attrs'))->render();
    }, [...]);
}
```

### 4. Shortcode "DB-driven" (consulta DB)

Ejemplo: `[blog-posts limit="6" source="latest"]`

```php
protected function registerBlogPosts(): void
{
    $this->compiler->register('blog-posts', function (array $attrs): string {
        $limit = max(1, min(20, (int) ($attrs['limit'] ?? 6)));
        $source = $attrs['source'] ?? 'latest';
        
        try {
            $posts = \Modules\Blog\Models\Post::with(['author', 'category'])
                ->withCount('comments')
                ->when($source === 'featured', fn ($q) => $q->where('is_featured', true))
                ->when($source === 'latest', fn ($q) => $q->latest())
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return '<!-- TODO: Connect to Blog module -->';
        }
        
        return view('{slug}::shortcodes.blog-posts', compact('attrs', 'posts'))->render();
    }, [...]);
}
```

### 5. Shortcode con plugin JS pesado

Ejemplo: `[slider]` con Owl Carousel o Swiper

```php
protected function registerSlider(): void
{
    $this->compiler->register('slider', function (array $attrs, string $content): string {
        return view('{slug}::shortcodes.slider', compact('attrs', 'content'))->render();
    }, [...]);
}
```

```blade
{{-- slider.blade.php --}}
@php
    $autoplay = filter_var($attrs['autoplay'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $items = (int) ($attrs['items'] ?? 1);
    $effect = $attrs['effect'] ?? 'slide';  // slide | fade
    
    $swiperOptions = json_encode([
        'slidesPerView' => $items,
        'autoplay' => $autoplay ? ['delay' => (int) ($attrs['autoplay-speed'] ?? 5000)] : false,
        'effect' => $effect,
        'loop' => true,
    ]);
@endphp

<div class="swiper {{ $attrs['class'] ?? '' }}" data-swiper-options='{!! $swiperOptions !!}'>
    <div class="swiper-wrapper">
        {!! $content !!}
    </div>
    <div class="swiper-pagination"></div>
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    @endpush
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
        <script>
            document.querySelectorAll('[data-swiper-options]').forEach(el => {
                const options = JSON.parse(el.dataset.swiperOptions);
                new Swiper(el, options);
            });
        </script>
    @endpush
@endonce
```

### 6. Shortcode con animación CSS / vanilla JS

Ejemplo: `[counter]` con IntersectionObserver

```blade
@php
    $number = (int) ($attrs['number'] ?? 0);
    $duration = (int) ($attrs['duration'] ?? 2000);
    $suffix = $attrs['suffix'] ?? '';
    $label = $attrs['label'] ?? '';
@endphp

<div class="counter" data-counter="{{ $number }}" data-duration="{{ $duration }}">
    <span class="counter-number">0</span><span class="counter-suffix">{{ $suffix }}</span>
    @if($label)
        <p class="counter-label">{{ $label }}</p>
    @endif
</div>

@once
    @push('scripts')
        <script>
            (function() {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const el = entry.target;
                            const target = parseInt(el.dataset.counter);
                            const duration = parseInt(el.dataset.duration);
                            const numberEl = el.querySelector('.counter-number');
                            const start = performance.now();
                            
                            function step(now) {
                                const progress = Math.min((now - start) / duration, 1);
                                numberEl.textContent = Math.floor(target * progress);
                                if (progress < 1) requestAnimationFrame(step);
                            }
                            requestAnimationFrame(step);
                            observer.unobserve(el);
                        }
                    });
                }, { threshold: 0.3 });
                
                document.addEventListener('DOMContentLoaded', () => {
                    document.querySelectorAll('[data-counter]').forEach(el => observer.observe(el));
                });
            })();
        </script>
    @endpush
@endonce
```

## Atributos comunes (extraíbles a trait/mixin)

Estos atributos aparecen en muchos shortcodes — considera extraer a un helper:

| Atributo | Uso | Validación |
|----------|-----|-----------|
| `class` | Clases CSS extras | regex: `[a-z0-9\s\-_]` max 100 |
| `id` | ID del elemento | regex: `[a-z0-9\-]` max 50 |
| `align` | Alineación | enum: left\|center\|right |
| `bg-color` | Color fondo | regex: `#[0-9a-fA-F]{3,6}` |
| `image` | URL imagen | validate URL |
| `cols` / `cols-md` / `cols-sm` | Grid columns | int 1-12 |
| `gap` | Bootstrap gap | int 0-5 |
| `animation` | Nombre animación | whitelist animate.css |
| `delay` | Delay animación | regex: `\d+(\.\d+)?s` |

## Resumen de "DO" y "DON'T"

### DO
- ✅ Validar enums con whitelist + fallback a default
- ✅ `htmlspecialchars()` en atributos string
- ✅ `loading="lazy"` y dimensiones en `<img>`
- ✅ `@once + @push` para JS/CSS de plugins
- ✅ `data-bg-image` para imágenes dinámicas (no inline style)
- ✅ Multi-idioma con `__('shortcode::messages.X')`
- ✅ FA6 only (`fas fa-*`, `far fa-*`, `fab fa-*`)
- ✅ Bootstrap 5.3 nativo (`data-bs-toggle`, `bootstrap.Modal`)
- ✅ Test happy path + edge cases

### DON'T
- ❌ NO `style=""` inline (excepción: posicionamiento dinámico hotspot-pin)
- ❌ NO `d-icon-*` (icon font Riode no cargada)
- ❌ NO Tabler Icons (`ti ti-*`)
- ❌ NO Livewire / Inertia / React / Vue / Alpine
- ❌ NO plugins JS pesados sin Plan B (skrollr deprecado, magnific → BS modal)
- ❌ NO consultas N+1 (siempre eager loading)
- ❌ NO HTML inseguro sin escape
