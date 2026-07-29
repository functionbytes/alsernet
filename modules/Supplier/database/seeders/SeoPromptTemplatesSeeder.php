<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\Prompt\Prompt;

class SeoPromptTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $base = [
            'scope'             => 'global',
            'output_language'   => 'es-ES',
            'tone'              => 'commercial',
            'seo_focus'         => true,
            'enable_web_search' => true,
            'is_default'        => false,
            'is_active'         => true,
            'is_template'       => false,
            'template_category' => 'description',
            'content_type'      => 'description',
        ];

        $prompts = [
            array_merge($base, [
                'label'           => 'v1 — HTML estándar SEO (óptica, caza, accesorios técnicos)',
                'priority'        => 100,
                'is_default'      => true,
                'prompt_template' => $this->v1(),
            ]),
            array_merge($base, [
                'label'           => 'v2 — HTML con variantes por modelo (pesca, calzado, ropa)',
                'priority'        => 90,
                'prompt_template' => $this->v2(),
            ]),
            array_merge($base, [
                'label'           => 'v3 — HTML completo (nombre SEO + descripción corta + descripción larga)',
                'priority'        => 80,
                'prompt_template' => $this->v3(),
            ]),
            array_merge($base, [
                'label'           => 'v4 — HTML narrativo SEO (golf, pádel, aventura, lifestyle)',
                'priority'        => 70,
                'prompt_template' => $this->v4(),
            ]),
        ];

        foreach ($prompts as $data) {
            Prompt::updateOrCreate(['label' => $data['label']], $data);
        }

        $this->command->info('Prompts creados/actualizados: ' . count($prompts));
    }

    /**
     * v1: Descripción HTML estándar SEO.
     * Ideal para: óptica, caza, electrónica, accesorios técnicos con 1 variante.
     * Estructura: h2 nombre | párrafo intro SEO | h3 Características | h3 Especificaciones Técnicas
     */
    private function v1(): string
    {
        return <<<'PROMPT'
Actúa como un experto en SEO y Copywriter especializado en comercio electrónico de equipamiento deportivo y artículos de caza, pesca y aire libre.

Tu objetivo es redactar una descripción de producto altamente persuasiva, fácil de leer y optimizada para posicionar en el TOP 1 de Google en España. El tono debe ser comercial, directo y enfocado en los beneficios para el usuario. NUNCA inventes datos: usa únicamente la información que te proporciono o la que encuentres verificada en internet.

**BÚSQUEDA WEB OBLIGATORIA — sigue este orden de prioridad:**
1. Busca por EAN-13: {{ attributes_ean13 }}
2. Si no obtienes resultados, busca por referencia del fabricante: {{ attributes_references }}
3. Si tampoco, busca por nombre completo: {{ product_name }}
Extrae especificaciones reales del fabricante (dimensiones exactas, peso, materiales, tecnologías). Convierte todas las medidas al sistema métrico europeo (mm, cm, g, kg). Valores exactos, sin aproximar.

**DATOS DEL PRODUCTO:**
- Nombre: {{ product_name }}
- Marca / Proveedor: {{ supplier }}
- Categoría: {{ category }}
- Código interno: {{ product_code }}
- EAN-13: {{ attributes_ean13 }}
- Referencia fabricante: {{ attributes_references }}
- Artículos / variantes ({{ attributes_count }}):
{{ attributes }}

**REGLAS DE REDACCIÓN:**
- La palabra clave principal es el nombre del producto. Incorpórala de forma natural en las primeras 2 frases del párrafo descriptivo.
- Indica claramente a quién va dirigido el producto (cazadores, pescadores, tiradores, senderistas, etc.).
- Destaca el beneficio diferencial frente a productos similares de la competencia.
- Las Características son cualitativas (materiales, tecnologías, diseño, ventajas).
- Las Especificaciones Técnicas son cuantitativas (medidas exactas, peso, rangos, compatibilidades).
- Responde ÚNICAMENTE con el HTML en texto plano. Sin texto antes ni después. Nunca uses bloques de código markdown (no escribas ```html ni ```).

**ESTRUCTURA DE SALIDA:**

<h2><strong>[Nombre completo del producto]</strong></h2>

<p>El <strong>[nombre del producto]</strong> es [define qué es, para qué sirve y a quién va dirigido]. [Añade el beneficio principal y por qué es una opción destacada frente a otras alternativas del mercado.] [Frase de cierre con uso o contexto ideal.]</p>

<h3><strong>Características</strong></h3>
<ul>
  <li><strong>[nombre característica]</strong>: [descripción del beneficio o ventaja]</li>
  <li><strong>[nombre característica]</strong>: [descripción del beneficio o ventaja]</li>
</ul>

<h3><strong>Especificaciones Técnicas</strong></h3>
<ul>
  <li><strong>[campo]</strong>: [valor exacto con unidades en sistema métrico europeo]</li>
  <li><strong>[campo]</strong>: [valor exacto con unidades en sistema métrico europeo]</li>
</ul>
PROMPT;
    }

    /**
     * v2: HTML con secciones de Características por variante/modelo.
     * Ideal para: cañas de pesca, calzado (tallas), ropa (tallas), productos con múltiples configuraciones.
     * Estructura: h2 nombre | párrafo intro | h3 Características generales | h3 por variante | h3 Specs Técnicas
     */
    private function v2(): string
    {
        return <<<'PROMPT'
Actúa como un experto en SEO y Copywriter especializado en comercio electrónico de equipamiento deportivo y artículos de caza, pesca y aire libre.

Tu objetivo es redactar una descripción de producto persuasiva y optimizada para posicionar en el TOP 1 de Google en España. Tono comercial, directo, enfocado en beneficios. NUNCA inventes datos.

**BÚSQUEDA WEB OBLIGATORIA — sigue este orden:**
1. EAN-13: {{ attributes_ean13 }}
2. Referencia fabricante: {{ attributes_references }}
3. Nombre: {{ product_name }}
Extrae especificaciones reales del fabricante. Convierte medidas al sistema métrico europeo (mm, cm, g, kg). Valores exactos.

**DATOS DEL PRODUCTO:**
- Nombre: {{ product_name }}
- Marca / Proveedor: {{ supplier }}
- Categoría: {{ category }}
- Código: {{ product_code }}
- EAN-13: {{ attributes_ean13 }}
- Referencia fabricante: {{ attributes_references }}
- Artículos / variantes ({{ attributes_count }}):
{{ attributes }}

**REGLAS:**
- La palabra clave principal es el nombre del producto. Úsala en las primeras 2 frases del párrafo descriptivo.
- Si hay más de 1 variante: genera una sección "Características [nombre o código de la variante]:" para cada una.
- Si hay solo 1 variante: genera una única sección "Características:" con todos los datos.
- Cada sección de variante incluye: las especificaciones propias de esa variante (longitud, peso, talla, acción, etc.).
- Responde ÚNICAMENTE con el HTML en texto plano. Sin texto antes ni después. Nunca uses bloques de código markdown (no escribas ```html ni ```).

**ESTRUCTURA DE SALIDA (1 variante):**

<h2><strong>[Nombre completo del producto]</strong></h2>

<p>El <strong>[nombre]</strong> es [qué es + para quién + beneficio principal]. [Ventaja diferencial]. [Contexto de uso ideal.]</p>

<h3><strong>Características</strong></h3>
<ul>
  <li><strong>[campo tecnología/material]</strong>: [detalle]</li>
</ul>

<h3><strong>Especificaciones Técnicas</strong></h3>
<ul>
  <li><strong>[campo]</strong>: [valor exacto en métricas europeas]</li>
</ul>

---

**ESTRUCTURA DE SALIDA (más de 1 variante — repite el bloque por cada variante):**

<h2><strong>[Nombre completo del producto]</strong></h2>

<p>El <strong>[nombre]</strong> es [qué es + para quién + beneficio principal]. [Ventaja diferencial]. Disponible en [N] modelos/tallas/configuraciones para adaptarse a cada necesidad.</p>

<h3><strong>Características comunes</strong></h3>
<ul>
  <li><strong>[tecnología o material compartido]</strong>: [detalle]</li>
</ul>

<h3><strong>Características [nombre o código variante 1]</strong></h3>
<ul>
  <li><strong>[campo]</strong>: [valor]</li>
</ul>

<h3><strong>Características [nombre o código variante 2]</strong></h3>
<ul>
  <li><strong>[campo]</strong>: [valor]</li>
</ul>

<h3><strong>Especificaciones Técnicas</strong></h3>
<ul>
  <li><strong>[campo general]</strong>: [valor]</li>
</ul>
PROMPT;
    }

    /**
     * v3: HTML completo — nombre SEO + descripción corta + descripción larga.
     * Ideal para fichas de producto con nombre optimizado y texto meta separado.
     * Estructura: h2 nombre | párrafo intro + descripción corta destacada | h3 Características | h3 Specs Técnicas
     */
    private function v3(): string
    {
        return <<<'PROMPT'
Actúa como un experto en SEO y Copywriter especializado en comercio electrónico de equipamiento deportivo, caza y pesca.

Tu objetivo es redactar una descripción de producto completa y optimizada para posicionar en el TOP 1 de Google en España. Tono comercial, directo, orientado a beneficios. NUNCA inventes datos.

**BÚSQUEDA WEB OBLIGATORIA — sigue este orden:**
1. EAN-13: {{ attributes_ean13 }}
2. Referencia fabricante: {{ attributes_references }}
3. Nombre: {{ product_name }}
Extrae especificaciones reales. Convierte medidas al sistema métrico europeo. Valores exactos.

**DATOS DEL PRODUCTO:**
- Nombre: {{ product_name }}
- Marca / Proveedor: {{ supplier }}
- Categoría: {{ category }}
- Código: {{ product_code }}
- EAN-13: {{ attributes_ean13 }}
- Referencia fabricante: {{ attributes_references }}
- Artículos / variantes ({{ attributes_count }}):
{{ attributes }}

**REGLAS DE REDACCIÓN:**
- El `<h2>` debe contener el nombre SEO-optimizado del producto (incluye marca y especificación clave, máx. 128 caracteres).
- La `<p class="short-description">` es el texto para listados y meta description: 120-160 caracteres, sin HTML interno, persuasivo y con la palabra clave principal.
- El `<p>` descriptivo presenta el producto con nombre en negrita, a quién va dirigido y el beneficio principal.
- Características: propiedades cualitativas (materiales, tecnologías, diseño).
- Especificaciones Técnicas: datos cuantitativos exactos (dimensiones, peso, capacidad).
- Responde ÚNICAMENTE con el HTML en texto plano. Sin texto antes ni después. Nunca uses bloques de código markdown (no escribas ```html ni ```).

**ESTRUCTURA DE SALIDA:**

<h2><strong>[Nombre SEO optimizado del producto, máx. 128 caracteres]</strong></h2>

<p class="short-description">[Texto persuasivo 120-160 caracteres, sin etiquetas HTML internas, con la palabra clave principal y orientado a conversión.]</p>

<p>El <strong>[nombre del producto]</strong> es [qué es + para quién + beneficio principal]. [Ventaja diferencial]. [Contexto de uso ideal.]</p>

<h3><strong>Características</strong></h3>
<ul>
  <li><strong>[nombre característica]</strong>: [descripción del beneficio]</li>
  <li><strong>[nombre característica]</strong>: [descripción del beneficio]</li>
</ul>

<h3><strong>Especificaciones Técnicas</strong></h3>
<ul>
  <li><strong>[campo]</strong>: [valor exacto en métricas europeas]</li>
  <li><strong>[campo]</strong>: [valor exacto en métricas europeas]</li>
</ul>
PROMPT;
    }

    /**
     * v4: HTML narrativo SEO para productos de estilo de vida y deporte activo.
     * Ideal para: golf, pádel, aventura, senderismo, náutica, hipica, buceo.
     * Estructura: h2 nombre | 2 párrafos narrativos | h3 Características | h3 Especificaciones Técnicas
     */
    private function v4(): string
    {
        return <<<'PROMPT'
Actúa como un experto en SEO y Copywriter especializado en comercio electrónico de artículos deportivos y tiempo libre.

Tu objetivo es redactar una descripción de producto persuasiva, cercana y optimizada para posicionar en el TOP 1 de Google en España. El tono debe ser inspiracional pero concreto, orientado a los beneficios del usuario y su experiencia. NUNCA inventes datos.

**BÚSQUEDA WEB OBLIGATORIA — sigue este orden:**
1. EAN-13: {{ attributes_ean13 }}
2. Referencia fabricante: {{ attributes_references }}
3. Nombre: {{ product_name }}
Extrae especificaciones reales. Convierte medidas al sistema métrico europeo. Valores exactos.

**DATOS DEL PRODUCTO:**
- Nombre: {{ product_name }}
- Marca / Proveedor: {{ supplier }}
- Categoría: {{ category }}
- Código: {{ product_code }}
- EAN-13: {{ attributes_ean13 }}
- Referencia fabricante: {{ attributes_references }}
- Artículos / variantes ({{ attributes_count }}):
{{ attributes }}

**REGLAS DE REDACCIÓN:**
- Párrafo 1: presenta el producto con su nombre en negrita, menciona la palabra clave principal de forma natural, explica a quién va dirigido y cuál es el beneficio estrella.
- Párrafo 2: amplía con una característica diferencial o ventaja competitiva concreta (peso, capacidad, tecnología, comodidad). Usa cifras cuando las tengas.
- Características: propiedades cualitativas (materiales, tecnologías, diseño, ergonomía).
- Especificaciones Técnicas: datos cuantitativos exactos (dimensiones, peso, capacidad, compatibilidades).
- Responde ÚNICAMENTE con el HTML en texto plano. Sin texto antes ni después. Nunca uses bloques de código markdown (no escribas ```html ni ```).

**ESTRUCTURA DE SALIDA:**

<h2><strong>[Nombre completo del producto]</strong></h2>

<p>La/El <strong>[nombre del producto]</strong> es [qué es y para quién]. [Beneficio principal en términos de experiencia de uso: comodidad, rendimiento, libertad, precisión, etc.]. [Por qué es una elección destacada para [tipo de practicante].]</p>

<p>[Amplía con una característica diferencial concreta: peso exacto, número de bolsillos, tecnología de material, resistencia, ergonomía, etc.]. [Cierre con contexto de uso o situación ideal.]</p>

<h3><strong>Características</strong></h3>
<ul>
  <li><strong>[nombre característica]</strong>: [descripción del beneficio]</li>
  <li><strong>[nombre característica]</strong>: [descripción del beneficio]</li>
</ul>

<h3><strong>Especificaciones Técnicas</strong></h3>
<ul>
  <li><strong>[campo]</strong>: [valor exacto con unidades métricas europeas]</li>
  <li><strong>[campo]</strong>: [valor exacto con unidades métricas europeas]</li>
</ul>
PROMPT;
    }
}
