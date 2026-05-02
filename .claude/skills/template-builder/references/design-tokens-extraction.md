# Design Tokens Extraction — Cómo extraer paleta/fonts del CSS

## Objetivo

Extraer del CSS de la plantilla original:
1. Paleta de colores (primary, secondary, accent, status, neutrals)
2. Tipografía (fonts, sizes, weights)
3. Spacing scale
4. Border-radius
5. Shadows
6. Breakpoints

Generar `tokens.css` con variables CSS adaptadas al brand del proyecto.

## Fase 1 — Detectar si el CSS usa custom properties

```bash
CSS_FILE=$TEMPLATE_PATH/css/style.min.css
CSS_RAW=$TEMPLATE_PATH/css/style.css  # si existe la versión no minificada

# 1. Si usa CSS variables modernas, copiar directamente:
grep -oE '\-\-[a-z-]+:\s*[^;}]+' $CSS_FILE | sort -u | head -30
```

Si encuentra variables (`--color-primary: #...`):
- ✅ Copiarlas directamente al `tokens.css` del template
- Sustituir `--color-primary` por el del proyecto Alsernet

Si NO encuentra variables (CSS clásico hard-coded):
- Continuar a Fase 2

## Fase 2 — Detectar colores hard-coded

### Estrategia 1: hex repetidos

```bash
# Hex con frecuencia (más usado = más probable que sea color brand)
grep -oE '#[0-9a-fA-F]{6}' $CSS_FILE | sort | uniq -c | sort -rn | head -20
```

Output ejemplo (Riode):
```
   1247 #fff
    348 #222
    156 #26c           ← color primario candidato
     89 #d26e4b        ← secundario candidato  
     45 #ff6b6b
```

### Estrategia 2: Buscar `color:` en clases conocidas

```bash
# Color del .btn-primary
grep -A 3 "\.btn-primary" $CSS_FILE | grep -oE "(background|color):\s*[^;}]+" | head

# Color del .text-primary
grep -A 2 "\.text-primary" $CSS_FILE | grep -oE "color:\s*[^;}]+" | head

# Color de links (a:hover)
grep -A 2 "a:hover" $CSS_FILE | grep -oE "color:\s*[^;}]+" | head
```

### Estrategia 3: Heuristica visual (Chrome DevTools)

Si las búsquedas anteriores son ambiguas, usar Chrome DevTools:

```bash
# Renderizar la home en Chrome
mcp__chrome-devtools__navigate_page http://localhost:8765/index.html
mcp__chrome-devtools__evaluate_script: () => {
    return getComputedStyle(document.querySelector('.btn-primary')).backgroundColor;
}
```

## Fase 3 — Mapeo a brand Alsernet

Una vez identificados los colores del template original, mapear:

| Original | Reemplazar por | Variable destino |
|----------|----------------|------------------|
| Primary del template | `#b10100` (Alsernet) | `--color-primary` |
| Hover del primary | `#7da010` (10% más oscuro) | `--color-primary-hover` |
| Light del primary | `#d3e8a8` (70% lightness) | `--color-primary-light` |
| Secondary | `#222529` (dark) | `--color-secondary` |
| Accent | `#FA896B` | `--color-accent` |

Para los neutrals (text, border, bg) — mantener los del template original (suelen ser estándar).

## Fase 4 — Detectar fonts

### Estrategia 1: Imports Google Fonts en HTML

```bash
grep -h "fonts.googleapis.com" $TEMPLATE_PATH/*.html | sort -u
```

Output ejemplo:
```html
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
```

→ Font primaria: **Poppins**, weights 300-800

### Estrategia 2: `font-family` en CSS

```bash
grep -oE 'font-family:[^;]+' $CSS_FILE | sort | uniq -c | sort -rn | head
```

Output:
```
    782 font-family: 'Poppins', sans-serif
     45 font-family: 'Jost', sans-serif    ← font display vertical
      3 font-family: 'Kalam', cursive       ← handwritten para food2
```

### Estrategia 3: Fonts por demo vertical

Si la plantilla tiene demos verticales (beauty, cake, sport, etc.), cada una puede tener fuente display distinta:

```bash
# Buscar @import en CSS específicos por demo
for demo in beauty cake food yoga; do
    echo "=== demo-$demo ==="
    grep -h "fonts.googleapis.com" $TEMPLATE_PATH/demo-$demo.html | head -1
done
```

Output Riode:
- `demo-beauty`: Jost
- `demo-food2`: Kalam
- `demo-tea`: Delius
- `demo-food3`: Rammetto One

→ En `tokens.css` ofrecer override por vertical:
```css
:root {
    --font-base: 'Poppins', system-ui, sans-serif;
    --font-display: 'Poppins', sans-serif;
}

/* Override por demo */
[data-demo="beauty"] { --font-display: 'Jost', sans-serif; }
[data-demo="food2"] { --font-display: 'Kalam', cursive; }
```

## Fase 5 — Spacing scale

### Estrategia 1: padding/margin más usados

```bash
grep -oE '(padding|margin):\s*[^;}]+' $CSS_FILE | grep -oE '\d+px' | sort | uniq -c | sort -rn | head -20
```

Output típico:
```
   2348 16px      ← spacing-3
   1567 24px      ← spacing-4
    892 8px       ← spacing-2
    623 48px      ← spacing-5
```

### Estrategia 2: Detectar si el template usa Bootstrap

```bash
grep "bootstrap" $TEMPLATE_PATH/*.html | head -1
grep "bootstrap" $CSS_FILE | head -1
```

Si SÍ usa Bootstrap → usar su scale estándar:
```
4 / 8 / 12 / 16 / 20 / 24 / 32 / 40 / 48 / 64 / 80 / 96 / 128 px
```

## Fase 6 — Border radius

```bash
grep -oE 'border-radius:\s*[^;}]+' $CSS_FILE | sort | uniq -c | sort -rn | head
```

Output:
```
    234 border-radius: 4px       ← --radius-md
    156 border-radius: 50%       ← --radius-circle
     89 border-radius: 50rem     ← --radius-pill
     45 border-radius: 8px       ← --radius-lg
```

## Fase 7 — Shadows

```bash
grep -oE 'box-shadow:\s*[^;}]+' $CSS_FILE | sort | uniq -c | sort -rn | head
```

Output:
```
    34 box-shadow: 0 2px 6px rgba(0,0,0,0.06)        ← --shadow-card
    12 box-shadow: 0 4px 16px rgba(0,0,0,0.12)       ← --shadow-card-hover
     8 box-shadow: 0 8px 24px rgba(0,0,0,0.15)       ← --shadow-lg
```

## Fase 8 — Breakpoints

```bash
grep -oE '@media[^{]+' $CSS_FILE | sort | uniq -c | sort -rn | head
```

Detectar breakpoints reales del template. Idealmente coinciden con Bootstrap 5.3:
```
xs:  <576px     sm: ≥576px      md: ≥768px
lg: ≥992px     xl: ≥1200px     xxl: ≥1400px
```

Si el template usa breakpoints distintos (común en Riode: 480, 1600), añadir overrides al tokens.css.

## Output final: `tokens.css` template

```css
/* ============================================================================
   TEMPLATE {NAME} → ALSERNET — Design Tokens
   Origen: {URL/Path}
   Color primario original: {color del template}
   Sustituido por: #b10100 (verde Alsernet)
   ============================================================================ */

:root {
    /* ────────────────────────────────────────
       BRAND COLORS (Alsernet)
       ──────────────────────────────────────── */
    --color-primary: #b10100;
    --color-primary-hover: #7da010;
    --color-primary-light: #d3e8a8;
    --color-primary-dark: #5e7d0c;
    
    --color-secondary: {extraído};
    --color-accent: {extraído};
    
    /* ────────────────────────────────────────
       STATUS COLORS
       ──────────────────────────────────────── */
    --color-success: {extraído o #13C672};
    --color-danger: {extraído o #FA896B};
    --color-warning: {extraído o #FEC90F};
    --color-info: {extraído o #4990D9};
    
    /* ────────────────────────────────────────
       NEUTRALS
       ──────────────────────────────────────── */
    --color-text: {extraído};
    --color-text-muted: {extraído};
    --color-border: {extraído};
    --color-bg: {extraído o #FFFFFF};
    --color-bg-alt: {extraído};
    --color-bg-dark: {extraído};
    
    /* ────────────────────────────────────────
       TYPOGRAPHY
       ──────────────────────────────────────── */
    --font-base: '{detectado}', system-ui, sans-serif;
    --font-display: '{detectado}', sans-serif;
    
    --fs-xs: 0.75rem;
    --fs-sm: 0.875rem;
    --fs-base: 1rem;
    --fs-lg: 1.125rem;
    --fs-xl: 1.25rem;
    --fs-2xl: 1.5rem;
    --fs-3xl: 1.875rem;
    --fs-4xl: 2.25rem;
    --fs-5xl: 3rem;
    
    --fw-light: 300;
    --fw-normal: 400;
    --fw-medium: 500;
    --fw-semibold: 600;
    --fw-bold: 700;
    --fw-black: 800;
    
    /* ────────────────────────────────────────
       SPACING
       ──────────────────────────────────────── */
    --space-1: 0.25rem;
    --space-2: 0.5rem;
    --space-3: 1rem;
    --space-4: 1.5rem;
    --space-5: 3rem;
    
    /* ────────────────────────────────────────
       BORDER RADIUS
       ──────────────────────────────────────── */
    --radius-sm: 2px;
    --radius-md: {extraído};
    --radius-lg: 8px;
    --radius-pill: 50rem;
    --radius-circle: 50%;
    
    /* ────────────────────────────────────────
       SHADOWS
       ──────────────────────────────────────── */
    --shadow-card: {extraído};
    --shadow-card-hover: {extraído};
    --shadow-lg: 0 8px 24px rgba(0,0,0,0.15);
}
```

## Marcadores especiales en tokens.css

Si un valor NO se pudo extraer con confianza:

```css
:root {
    --color-text: [INFERIDO]/* visualmente parece #222 */ #222529;
    --shadow-md: [VERIFICAR]/* poco confiable */ 0 4px 8px rgba(0,0,0,0.08);
}
```

Documentar en el output al usuario qué tokens son `[INFERIDO]` para validación visual.

## Comandos rápidos para extracción

```bash
# Script all-in-one para extraer tokens
TEMPLATE_PATH="/Users/.../template-name"
CSS_FILE="$TEMPLATE_PATH/css/style.min.css"
OUT_DIR="$TEMPLATE_PATH/../template-analisis"
mkdir -p $OUT_DIR

echo "=== Top 10 colores hex ===" > $OUT_DIR/tokens-raw.txt
grep -oE '#[0-9a-fA-F]{6}' $CSS_FILE | sort | uniq -c | sort -rn | head -10 >> $OUT_DIR/tokens-raw.txt

echo "" >> $OUT_DIR/tokens-raw.txt
echo "=== Top 5 fonts ===" >> $OUT_DIR/tokens-raw.txt
grep -oE 'font-family:[^;]+' $CSS_FILE | sort | uniq -c | sort -rn | head -5 >> $OUT_DIR/tokens-raw.txt

echo "" >> $OUT_DIR/tokens-raw.txt
echo "=== Top 5 border-radius ===" >> $OUT_DIR/tokens-raw.txt
grep -oE 'border-radius:[^;}]+' $CSS_FILE | sort | uniq -c | sort -rn | head -5 >> $OUT_DIR/tokens-raw.txt

echo "" >> $OUT_DIR/tokens-raw.txt
echo "=== Box shadows ===" >> $OUT_DIR/tokens-raw.txt
grep -oE 'box-shadow:[^;}]+' $CSS_FILE | sort | uniq -c | sort -rn | head -5 >> $OUT_DIR/tokens-raw.txt

cat $OUT_DIR/tokens-raw.txt
```
