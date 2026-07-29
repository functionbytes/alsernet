<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Models\Supplier\Supplier;

class SupplierPromptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nike = Supplier::where('code', 'NIKE')->first();
        $adidas = Supplier::where('code', 'ADIDAS')->first();

        $prompts = [
            // 1. Global Default Prompt (Lowest Priority)
            [
                'supplier_id' => null,
                'category_id' => null,
                'source_id' => null,
                'scope' => 'global',
                'label' => 'Prompt Global Por defecto',
                'prompt_template' => $this->getGlobalPrompt(),
                'output_language' => 'es-ES',
                'tone' => 'commercial',
                'priority' => 100,
                'seo_focus' => true,
                'required_sections' => ['name', 'short_description', 'long_description', 'bullet_points', 'seo_title', 'seo_description'],
                'version' => 1,
                'is_default' => true,
                'is_active' => true,
            ],

            // 2. Supplier-Specific Prompt - Nike
            [
                'supplier_id' => $nike?->id,
                'category_id' => null,
                'source_id' => null,
                'scope' => 'supplier',
                'label' => 'Prompt Específico Nike',
                'prompt_template' => $this->getNikePrompt(),
                'output_language' => 'es-ES',
                'tone' => 'technical',
                'priority' => 10,
                'seo_focus' => true,
                'required_sections' => ['name', 'short_description', 'long_description', 'bullet_points', 'technologies', 'seo_title', 'seo_description'],
                'version' => 1,
                'is_default' => false,
                'is_active' => true,
            ],

            // 3. Supplier-Specific Prompt - Adidas
            [
                'supplier_id' => $adidas?->id,
                'category_id' => null,
                'source_id' => null,
                'scope' => 'supplier',
                'label' => 'Prompt Específico Adidas',
                'prompt_template' => $this->getAdidasPrompt(),
                'output_language' => 'es-ES',
                'tone' => 'commercial',
                'priority' => 10,
                'seo_focus' => true,
                'required_sections' => ['name', 'short_description', 'long_description', 'bullet_points', 'sustainability', 'seo_title', 'seo_description'],
                'version' => 1,
                'is_default' => false,
                'is_active' => true,
            ],

            // 4. Category-Specific Prompt - Zapatillas
            [
                'supplier_id' => null,
                'category_id' => null,
                'source_id' => null,
                'scope' => 'category',
                'label' => 'Prompt para Categoría Zapatillas',
                'prompt_template' => $this->getSneakersPrompt(),
                'output_language' => 'es-ES',
                'tone' => 'technical',
                'priority' => 20,
                'seo_focus' => true,
                'required_sections' => ['name', 'short_description', 'long_description', 'bullet_points', 'fit_guide', 'seo_title', 'seo_description'],
                'version' => 1,
                'is_default' => false,
                'is_active' => true,
            ],

            // 5. Category-Specific Prompt - Ropa
            [
                'supplier_id' => null,
                'category_id' => null,
                'source_id' => null,
                'scope' => 'category',
                'label' => 'Prompt para Categoría Ropa',
                'prompt_template' => $this->getClothingPrompt(),
                'output_language' => 'es-ES',
                'tone' => 'commercial',
                'priority' => 20,
                'seo_focus' => true,
                'required_sections' => ['name', 'short_description', 'long_description', 'bullet_points', 'care_instructions', 'size_guide', 'seo_title', 'seo_description'],
                'version' => 1,
                'is_default' => false,
                'is_active' => true,
            ],

            // 6. Supplier + Category Prompt - Nike Zapatillas
            [
                'supplier_id' => $nike?->id,
                'category_id' => null,
                'source_id' => null,
                'scope' => 'supplier',
                'label' => 'Prompt Nike Zapatillas',
                'prompt_template' => $this->getNikeSneakersPrompt(),
                'output_language' => 'es-ES',
                'tone' => 'technical',
                'priority' => 5,
                'seo_focus' => true,
                'required_sections' => ['name', 'short_description', 'long_description', 'bullet_points', 'technologies', 'fit_guide', 'seo_title', 'seo_description'],
                'version' => 1,
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($prompts as $promptData) {
            // Skip if supplier is required but not found
            if (isset($promptData['supplier_id']) && is_null($promptData['supplier_id']) && $promptData['scope'] === 'supplier') {
                continue;
            }

            Prompt::updateOrCreate(
                [
                    'label' => $promptData['label'],
                ],
                $promptData
            );
        }

        $this->command->info('Created supplier prompts with priority system');
    }

    /**
     * Global default prompt
     */
    private function getGlobalPrompt(): string
    {
        return <<<'PROMPT'
Eres un experto copywriter especializado en e-commerce y SEO. Genera contenido profesional para una ficha de producto web.

**DATOS DEL PRODUCTO:**
- Nombre: {{product_name}}
- Referencia ERP: {{erp_reference}}
- EAN: {{ean}}
- Proveedor: {{supplier_name}}
- Categoría: {{category_name}}
- Atributos: {{attributes}}

**INFORMACIÓN DISPONIBLE DE FUENTES:**
{{source_data}}

**INSTRUCCIONES:**
1. Crea un nombre de producto atractivo y descriptivo (máximo 128 caracteres)
2. Escribe una descripción corta persuasiva (120-160 caracteres)
3. Desarrolla una descripción larga detallada (300-500 palabras) con formato HTML:
   - Usa párrafos <p>
   - Destaca características clave con <strong>
   - Incluye listas con <ul> y <li>
4. Genera 5-8 puntos destacados en formato bullet points
5. Crea un meta título SEO optimizado (50-60 caracteres)
6. Escribe una meta descripción SEO (150-160 caracteres)
7. Sugiere 5-7 palabras clave SEO relevantes

**TONO:** Profesional, persuasivo y orientado a la venta

**FORMATO DE RESPUESTA:**
Devuelve un objeto JSON con esta estructura:
{
  "generated_name": "...",
  "short_description": "...",
  "long_description": "...",
  "bullet_points": ["...", "..."],
  "seo_title": "...",
  "seo_description": "...",
  "seo_keywords": "..."
}
PROMPT;
    }

    /**
     * Nike-specific prompt
     */
    private function getNikePrompt(): string
    {
        return <<<'PROMPT'
Eres un especialista en productos Nike con profundo conocimiento de sus tecnologías y filosofía de marca.

**DATOS DEL PRODUCTO NIKE:**
- Nombre: {{product_name}}
- Modelo: {{model_id}}
- Referencia: {{erp_reference}}
- Tecnologías Nike: {{nike_technologies}}
- Atributos: {{attributes}}

**INFORMACIÓN DE FUENTES NIKE:**
{{source_data}}

**CONOCIMIENTO DE TECNOLOGÍAS NIKE:**
- Nike Air: Sistema de amortiguación con cámara de aire
- Zoom Air: Amortiguación de bajo perfil para respuesta rápida
- React: Espuma ligera y reactiva
- Flyknit: Upper tejido sin costuras
- Dri-FIT: Tecnología de gestión de la humedad
- VaporMax: Amortiguación Air visible de longitud completa

**INSTRUCCIONES ESPECÍFICAS NIKE:**
1. Enfatiza las tecnologías Nike presentes en el producto
2. Destaca el rendimiento deportivo y la innovación
3. Menciona la herencia y legado del modelo si es icónico
4. Usa terminología técnica apropiada
5. Incluye información sobre ajuste y tallas (Nike suele tallar justo)
6. Destaca materiales premium y construcción de calidad

**TONO:** Técnico, inspirador, enfocado en rendimiento

**FORMATO DE RESPUESTA:**
{
  "generated_name": "Nike [Modelo] - [Tecnología Principal]",
  "short_description": "...",
  "long_description": "... [incluye sección de tecnologías]",
  "bullet_points": ["...", "..."],
  "technologies": ["Air Max", "React", "..."],
  "seo_title": "...",
  "seo_description": "...",
  "seo_keywords": "..."
}
PROMPT;
    }

    /**
     * Adidas-specific prompt
     */
    private function getAdidasPrompt(): string
    {
        return <<<'PROMPT'
Eres un experto en productos Adidas con conocimiento de sus tecnologías y compromiso con la sostenibilidad.

**DATOS DEL PRODUCTO ADIDAS:**
- Nombre: {{product_name}}
- Modelo: {{model_id}}
- Colección: {{collection}}
- Atributos: {{attributes}}

**INFORMACIÓN DE FUENTES ADIDAS:**
{{source_data}}

**TECNOLOGÍAS ADIDAS CLAVE:**
- Boost: Retorno de energía excepcional
- Primeknit: Upper tejido adaptable
- Continental Rubber: Suela con agarre superior
- Lightstrike: Amortiguación ligera
- Climacool: Ventilación 360 grados
- Cloudfoam: Comodidad superior

**SOSTENIBILIDAD ADIDAS:**
- Primegreen: Materiales reciclados de alto rendimiento
- Primeblue: Plástico reciclado del océano
- Compromiso con el medio ambiente

**INSTRUCCIONES ESPECÍFICAS ADIDAS:**
1. Destaca tecnologías Adidas presentes
2. Menciona aspectos de sostenibilidad si aplica
3. Enfatiza estilo y versatilidad (deportivo + lifestyle)
4. Incluye información de colecciones icónicas (Originals, Performance, etc.)
5. Destaca colaboraciones especiales si las hay

**TONO:** Moderno, consciente, versátil entre deporte y estilo

**FORMATO DE RESPUESTA:**
{
  "generated_name": "Adidas [Modelo] - [Característica Principal]",
  "short_description": "...",
  "long_description": "... [incluye tecnologías y sostenibilidad]",
  "bullet_points": ["...", "..."],
  "sustainability": "...",
  "seo_title": "...",
  "seo_description": "...",
  "seo_keywords": "..."
}
PROMPT;
    }

    /**
     * Sneakers category prompt
     */
    private function getSneakersPrompt(): string
    {
        return <<<'PROMPT'
Eres un experto en calzado deportivo con conocimiento técnico de zapatillas.

**DATOS DE ZAPATILLAS:**
- Nombre: {{product_name}}
- Marca: {{supplier_name}}
- Uso principal: {{intended_use}}
- Talla: {{sizes}}

**INFORMACIÓN DE FUENTES:**
{{source_data}}

**ASPECTOS CLAVE PARA ZAPATILLAS:**
1. **Amortiguación:** Describe el sistema de amortiguación
2. **Upper/Corte:** Material y construcción
3. **Suela:** Tipo de suela y tracción
4. **Ajuste:** Información sobre tallaje y horma
5. **Peso:** Si disponible
6. **Uso recomendado:** Running, training, casual, basketball, etc.

**GUÍA DE AJUSTE:**
- Menciona si talla grande, pequeño o justo
- Recomienda si es apropiado para pies anchos
- Sugiere media talla arriba/abajo si es necesario

**FORMATO DE RESPUESTA:**
{
  "generated_name": "[Marca] [Modelo] - Zapatillas de [Uso]",
  "short_description": "...",
  "long_description": "... [incluye secciones: Amortiguación, Upper, Suela, Uso Recomendado]",
  "bullet_points": ["...", "..."],
  "fit_guide": "Estas zapatillas tallan...",
  "seo_title": "...",
  "seo_description": "...",
  "seo_keywords": "..."
}
PROMPT;
    }

    /**
     * Clothing category prompt
     */
    private function getClothingPrompt(): string
    {
        return <<<'PROMPT'
Eres un experto en ropa deportiva y casual con conocimiento de tejidos y construcción.

**DATOS DE PRENDA:**
- Nombre: {{product_name}}
- Marca: {{supplier_name}}
- Tipo: {{garment_type}}
- Composición: {{material_composition}}

**INFORMACIÓN DE FUENTES:**
{{source_data}}

**ASPECTOS CLAVE PARA ROPA:**
1. **Material y Tejido:** Composición y características
2. **Ajuste y Corte:** Fit (ajustado, regular, holgado)
3. **Características Técnicas:** Transpirabilidad, elasticidad, etc.
4. **Uso Recomendado:** Entrenamiento, casual, competición, etc.
5. **Detalles de Diseño:** Bolsillos, cremalleras, capucha, etc.

**INSTRUCCIONES DE CUIDADO:**
Incluye siempre instrucciones básicas de lavado y cuidado.

**GUÍA DE TALLAS:**
Menciona información sobre el ajuste y tallaje.

**FORMATO DE RESPUESTA:**
{
  "generated_name": "[Marca] [Tipo de Prenda] - [Característica]",
  "short_description": "...",
  "long_description": "... [incluye: Material, Ajuste, Características, Uso]",
  "bullet_points": ["...", "..."],
  "care_instructions": "Lavar a máquina a 30°C...",
  "size_guide": "Este modelo tiene un ajuste...",
  "seo_title": "...",
  "seo_description": "...",
  "seo_keywords": "..."
}
PROMPT;
    }

    /**
     * Nike Sneakers combined prompt (highest priority for Nike sneakers)
     */
    private function getNikeSneakersPrompt(): string
    {
        return <<<'PROMPT'
Eres EL experto definitivo en zapatillas Nike, combinando conocimiento técnico profundo con pasión por la innovación.

**DATOS DE ZAPATILLAS NIKE:**
- Nombre: {{product_name}}
- Modelo: {{model_id}}
- Tecnologías: {{nike_technologies}}
- Uso: {{intended_use}}

**INFORMACIÓN DE FUENTES NIKE:**
{{source_data}}

**ENFOQUE ESPECIALIZADO NIKE SNEAKERS:**
1. Identifica y destaca TODAS las tecnologías Nike presentes
2. Explica cómo cada tecnología beneficia al usuario
3. Menciona la herencia del modelo si es icónico (Air Max, Jordan, etc.)
4. Describe la experiencia de uso específica
5. Incluye información detallada de ajuste y tallaje Nike
6. Destaca colaboraciones o ediciones especiales

**ESTRUCTURA RECOMENDADA:**
- **Introducción:** Presenta el modelo y su propósito
- **Tecnologías:** Sección dedicada a explicar las tecnologías
- **Diseño y Construcción:** Upper, mediasuela, suela
- **Rendimiento:** Cómo se comporta en uso
- **Ajuste:** Información específica de tallaje Nike
- **Estilo:** Versatilidad y combinaciones

**TONO:** Entusiasta, técnico pero accesible, inspirador

**FORMATO DE RESPUESTA:**
{
  "generated_name": "Nike [Modelo] - [Tecnología Principal] - [Uso]",
  "short_description": "...",
  "long_description": "... [estructura completa con todas las secciones]",
  "bullet_points": ["...", "..."],
  "technologies": ["...", "..."],
  "fit_guide": "Las Nike [Modelo] tallan...",
  "seo_title": "...",
  "seo_description": "...",
  "seo_keywords": "..."
}
PROMPT;
    }
}
