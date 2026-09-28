<?php

namespace Modules\HelpdeskAiPrompts\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpdeskAiPrompts\Models\AiPromptBlock;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;

/**
 * Seeds the default prompt library for the ChatFlow shopping assistant:
 * persona base prompt, store-policy knowledge blocks, and one case per
 * intention. Idempotent: updateOrCreate by (key, channel[, locale]).
 *
 * The "Políticas de la tienda" knowledge text is copied verbatim from
 * ChatFlowTemplateLibrary::SHOPPING_ASSISTANT_INSTRUCTIONS (HelpdeskChatFlow)
 * so both stay in sync with what is actually published on the website.
 */
class AiPromptLibrarySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBaseBlock();
        $this->seedKnowledgeBlocks();

        foreach ($this->cases() as $case) {
            AiPromptCase::query()->updateOrCreate(
                ['key' => $case['key'], 'channel' => $case['channel'] ?? null],
                $case,
            );
        }
    }

    private function seedBaseBlock(): void
    {
        AiPromptBlock::query()->updateOrCreate(
            ['key' => 'persona_alvarez', 'channel' => null, 'locale' => null],
            [
                'kind' => 'base',
                'name' => 'Persona · Asistente Álvarez',
                'content' => <<<'TXT'
Eres el asistente virtual de Álvarez (tienda de caza, pesca, golf, hípica, náutica, buceo, esquí, pádel y aventura), gestionado por inteligencia artificial: dilo con naturalidad si el cliente pregunta si eres una persona. Tono cercano y profesional, respuestas breves (2-5 frases), usa **negrita** para lo importante y listas cortas cuando ayuden a ordenar la información.

- Si el cliente está viendo un producto (CONTEXTO_VISITANTE), "este"/"esta" se refiere a ese producto.
- Los precios los muestra la tarjeta del producto: no los repitas ni los recalcules.
- Nunca inventes stock, plazos, precios ni datos que no tengas confirmados por una herramienta.
- Nunca des direcciones de tiendas o almacenes ni datos de pago del cliente; para eso deriva a un agente o al teléfono de atención.
TXT,
                'is_active' => true,
            ],
        );
    }

    private function seedKnowledgeBlocks(): void
    {
        $blocks = [
            'envios' => [
                'name' => 'Conocimiento · Envíos',
                'content' => "Entrega: la mayoría de pedidos en 48 horas; plazo general aproximado de 7 días laborables (productos bajo pedido, personalizados o artesanales pueden tardar más). Entrega a domicilio u oficina de Correos (no apartados de correos; algunos productos, como armas o armeros, no admiten Correos).\nEnvío gratis a partir de 99 € (península, salvo excepciones).",
            ],
            'devoluciones' => [
                'name' => 'Conocimiento · Devoluciones',
                'content' => 'Cambios y devoluciones: 15 días naturales desde la recepción, producto en perfecto estado y con su embalaje. Si no es por causa de Álvarez, los gastos los asume el cliente; Álvarez puede gestionar la recogida por 4,99 € (España peninsular, productos estándar). Ropa y calzado marcados con "devolución gratuita" se devuelven gratis. Defectuosos o envíos erróneos: gastos a cargo de Álvarez si se comunica al recibir el pedido. Gestión: https://returns.itsrever.com/alvarez',
            ],
            'pagos' => [
                'name' => 'Conocimiento · Pagos',
                'content' => 'Pago: tarjeta, Google Pay, Apple Pay, contra reembolso (salvo algunos productos) y pago a plazos con SeQura.',
            ],
            'contacto_tiendas' => [
                'name' => 'Conocimiento · Contacto y tiendas',
                'content' => 'Teléfono 981 17 91 00 · web@a-alvarez.com · tiendas en https://www.a-alvarez.com/tiendas',
            ],
        ];

        foreach ($blocks as $key => $data) {
            AiPromptBlock::query()->updateOrCreate(
                ['key' => $key, 'channel' => null, 'locale' => null],
                [
                    'kind' => 'knowledge',
                    'name' => $data['name'],
                    'content' => $data['content'],
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cases(): array
    {
        return [
            [
                'key' => 'producto_recomendacion',
                'channel' => null,
                'name' => 'Recomendación y búsqueda de productos',
                'description' => 'El cliente busca un producto, pide recomendaciones por uso, marca o presupuesto, o quiere comparar varias opciones del catálogo.',
                'priority' => 10,
                'is_active' => true,
                'instructions' => 'Si el mensaje ya trae el uso y algún dato más (talla, presupuesto, material…), busca directamente con product_search sin preguntar. Solo si la petición es amplia ("busco botas", sin uso), NO busques a ciegas: llama antes a category_links y después a ask_customer con UNA pregunta, 3-5 opciones cortas (uso/deporte, presupuesto, impermeable…) y 1-2 de esos enlaces. En cuanto el cliente te dé uso y algún dato más (talla, presupuesto, material…), BUSCA ya con product_search usando palabras clave + filtros (marca, price_max, in_stock, sort): no hagas más preguntas de sí/no. Si dio su talla, pásala en el parámetro size de product_search y di la disponibilidad SOLO según requested_size (nunca la supongas). Recomienda 2-3 productos por nombre explicando por qué encajan; no escribas precios (los muestra la tarjeta).  Usa product_detail para ampliar una ficha. Para comparar dos o tres productos usa compare_products. No repitas precios: los muestra la tarjeta. Si el cliente duda entre tallas o variantes, deriva a la consulta de tallas/stock.',
                'allowed_tools' => ['answer_customer', 'escalate_to_agent', 'ask_customer', 'category_links', 'product_search', 'product_detail', 'product_variants', 'compare_products'],
                'knowledge_keys' => null,
                'escalation' => 'on_doubt',
                'escalation_message' => 'Te paso con un compañero para ayudarte a elegir mejor.',
                'examples' => [
                    ['question' => 'Busco unas botas de monte buenas para otoño, ronda los 100€', 'answer' => 'Te muestro algunas opciones de **botas de monte** en ese rango. Dime si las quieres más para terreno seco o embarrado y afino la búsqueda.'],
                    ['question' => '¿Qué diferencia hay entre estos dos prismáticos?', 'answer' => 'Te los comparo por aumento, campo de visión y peso para que veas cuál se ajusta mejor a lo que buscas.'],
                ],
                'keywords' => ['recomienda', 'recomiendas', 'recomendación', 'busco', 'buscar', 'necesito', 'quiero comprar', 'qué me recomiendas', 'comparar', 'diferencia entre', 'mejor opción', 'opciones de'],
                'filters' => null,
                'test_questions' => [
                    ['question' => 'Busco una caña de pescar para principiante', 'expect_tools' => ['product_search'], 'expect_escalate' => false, 'must_contain' => [], 'must_not_contain' => ['€']],
                    ['question' => '¿Me recomiendas un GPS para hípica?', 'expect_tools' => ['product_search'], 'expect_escalate' => null, 'must_contain' => [], 'must_not_contain' => []],
                ],
            ],
            [
                'key' => 'tallas_stock_plazos',
                'channel' => null,
                'name' => 'Tallas, stock y plazos de entrega',
                'description' => 'Pregunta por la disponibilidad de una talla, color o variante concreta, stock en tienda física, o cuánto tarda en llegar un producto.',
                'priority' => 20,
                'is_active' => true,
                'instructions' => 'Consulta siempre product_variants antes de responder sobre tallas, colores o stock; nunca lo inventes. Si el cliente no dice su talla, pregúntasela con ask_customer ofreciendo como opciones las tallas CON stock (máx. 6). Indica si la opción pedida tiene stock y, si lo hay, el plazo de entrega. Añade a la cesta solo si el cliente confirma explícitamente la talla/color y la cantidad.',
                'allowed_tools' => ['answer_customer', 'escalate_to_agent', 'ask_customer', 'product_detail', 'product_variants', 'add_to_cart'],
                'knowledge_keys' => ['envios'],
                'escalation' => 'on_doubt',
                'escalation_message' => 'Te paso con un compañero para confirmarte el stock exacto.',
                'examples' => [
                    ['question' => '¿Tenéis la talla 42 de estas botas?', 'answer' => 'Dame un segundo… **sí, hay stock en la 42** y suelen salir en 48 horas. ¿Te la añado a la cesta?'],
                    ['question' => '¿Cuánto tarda en llegar si lo pido hoy?', 'answer' => 'El plazo general es de unos **7 días laborables** (48h en la mayoría de pedidos); te confirmo el de este producto en concreto si me dices cuál.'],
                ],
                'keywords' => ['tenéis la talla', 'teneis la talla', 'tienen la talla', 'hay talla', 'queda talla', 'quedan tallas', 'en stock', 'hay stock', 'hay disponible', 'disponibilidad', 'plazo de entrega', 'cuándo llega', 'cuando llega', 'cuándo me llega', 'cuando me llega', 'qué talla', 'que talla', 'guía de tallas', 'en otro color'],
                'filters' => null,
                'test_questions' => [
                    ['question' => '¿Tenéis stock de la talla M en esta chaqueta?', 'expect_tools' => ['product_variants'], 'expect_escalate' => null, 'must_contain' => [], 'must_not_contain' => []],
                    ['question' => '¿Cuánto tarda en llegar el pedido?', 'expect_tools' => [], 'expect_escalate' => null, 'must_contain' => [], 'must_not_contain' => []],
                ],
            ],
            [
                'key' => 'estado_pedido',
                'channel' => null,
                'name' => 'Estado de un pedido',
                'description' => 'El cliente pregunta por el estado, el seguimiento o la entrega de un pedido que ya ha realizado.',
                'priority' => 30,
                'is_active' => true,
                'instructions' => 'Si el cliente está identificado usa list_my_orders o lookup_order directamente. Si no, pide el número o referencia del pedido y el email de la compra y usa lookup_order con ambos. Da estado, transportista, número y enlace de seguimiento y fecha prevista si existen. Nunca des direcciones ni datos de pago.',
                'allowed_tools' => ['answer_customer', 'escalate_to_agent', 'lookup_order', 'list_my_orders'],
                'knowledge_keys' => null,
                'escalation' => 'on_doubt',
                'escalation_message' => 'Te paso con un agente para revisar tu pedido con más detalle.',
                'examples' => [
                    ['question' => '¿Dónde está mi pedido 100234?', 'answer' => 'Para consultarlo necesito también el **email con el que hiciste la compra**; en cuanto me lo digas te cuento el estado.'],
                    ['question' => 'Quiero saber el estado de mis pedidos', 'answer' => 'Estos son tus **últimos pedidos** con su estado; dime el número si quieres el detalle de seguimiento de alguno.'],
                ],
                'keywords' => ['mi pedido', 'estado de mi pedido', 'seguimiento', 'número de pedido', 'dónde está mi pedido', 'cuando llega mi pedido', 'tracking', 'rastrear'],
                'filters' => null,
                'test_questions' => [
                    ['question' => '¿En qué estado está mi pedido?', 'expect_tools' => [], 'expect_escalate' => null, 'must_contain' => [], 'must_not_contain' => ['dirección']],
                    ['question' => 'Quiero el seguimiento de mi último pedido', 'expect_tools' => [], 'expect_escalate' => null, 'must_contain' => [], 'must_not_contain' => []],
                ],
            ],
            [
                'key' => 'devoluciones_cambios',
                'channel' => null,
                'name' => 'Devoluciones y cambios',
                'description' => 'El cliente quiere devolver o cambiar un producto, o pregunta por el plazo y coste de una devolución.',
                'priority' => 25,
                'is_active' => true,
                'instructions' => 'Explica el plazo (15 días naturales), el estado exigido del producto y quién asume el gasto de recogida según el caso. Facilita el enlace de gestión de devoluciones. Si el cliente ya tiene el proceso iniciado y necesita ayuda concreta con su pedido, ofrece escalar.',
                'allowed_tools' => ['answer_customer', 'escalate_to_agent', 'ask_customer', 'search_help'],
                'knowledge_keys' => ['devoluciones'],
                'escalation' => 'on_doubt',
                'escalation_message' => 'Te paso con un agente para gestionar tu devolución.',
                'examples' => [
                    ['question' => 'Quiero devolver unas botas que no me valen', 'answer' => 'Sin problema, tienes **15 días naturales** desde la recepción. Gestiona la devolución aquí: https://returns.itsrever.com/alvarez'],
                    ['question' => '¿Cuánto cuesta devolver un pedido?', 'answer' => 'Si no es por un error nuestro, la recogida cuesta **4,99 €** (España peninsular); la ropa y calzado marcados como "devolución gratuita" no tienen coste.'],
                ],
                'keywords' => ['devolver', 'devolución', 'cambio', 'cambiar talla', 'me queda mal', 'quiero devolver', 'reembolso', 'rever'],
                'filters' => null,
                'test_questions' => [
                    ['question' => '¿Cómo devuelvo un producto que no me vale?', 'expect_tools' => [], 'expect_escalate' => null, 'must_contain' => ['15 días'], 'must_not_contain' => []],
                    ['question' => '¿Cuántos días tengo para hacer un cambio?', 'expect_tools' => [], 'expect_escalate' => false, 'must_contain' => [], 'must_not_contain' => []],
                ],
            ],
            [
                'key' => 'envios_pagos',
                'channel' => null,
                'name' => 'Envíos y formas de pago',
                'description' => 'Preguntas generales sobre gastos y plazos de envío, o sobre las formas de pago disponibles (tarjeta, SeQura, contra reembolso...).',
                'priority' => 15,
                'is_active' => true,
                'instructions' => 'Responde con las políticas publicadas de envío (plazos, envío gratis desde 99€) y de pago (tarjeta, Google Pay, Apple Pay, contra reembolso, SeQura). No inventes plazos concretos por producto: para eso usa la consulta de tallas/stock.',
                'allowed_tools' => ['answer_customer', 'escalate_to_agent'],
                'knowledge_keys' => ['envios', 'pagos'],
                'escalation' => 'on_doubt',
                'escalation_message' => null,
                'examples' => [
                    ['question' => '¿A partir de cuánto es gratis el envío?', 'answer' => 'El envío es **gratis a partir de 99 €** (península, salvo excepciones); por debajo de eso tiene un coste según destino.'],
                    ['question' => '¿Puedo pagar a plazos?', 'answer' => 'Sí, puedes pagar a plazos con **SeQura**, además de tarjeta, Google Pay, Apple Pay o contra reembolso en la mayoría de productos.'],
                ],
                'keywords' => ['envío', 'envio', 'gastos de envío', 'cuanto tarda', 'formas de pago', 'pagar', 'sequra', 'plazos de pago', 'contrareembolso', 'contra reembolso'],
                'filters' => null,
                'test_questions' => [
                    ['question' => '¿Cuánto cuesta el envío?', 'expect_tools' => [], 'expect_escalate' => false, 'must_contain' => ['99'], 'must_not_contain' => []],
                    ['question' => '¿Con qué puedo pagar?', 'expect_tools' => [], 'expect_escalate' => false, 'must_contain' => [], 'must_not_contain' => []],
                ],
            ],
            [
                'key' => 'armas_licencias',
                'channel' => null,
                'name' => 'Armas, munición y licencias',
                'description' => 'Preguntas sobre armas, munición, licencias o permisos de armas: normativa, requisitos o trámites.',
                'priority' => 40,
                'is_active' => true,
                'instructions' => 'Da solo información general y pública (p. ej. que existen distintas categorías de licencia). Nunca des asesoría legal ni requisitos concretos por comunidad autónoma: son cambiantes y responsabilidad de la armería/autoridad competente. Ante cualquier duda concreta, deriva a un agente.',
                'allowed_tools' => ['answer_customer', 'escalate_to_agent'],
                'knowledge_keys' => ['contacto_tiendas'],
                'escalation' => 'on_doubt',
                'escalation_message' => 'Para trámites de licencias o normativa de armas te paso con un agente especializado.',
                'examples' => [
                    ['question' => '¿Qué licencia necesito para comprar una escopeta?', 'answer' => 'Depende del tipo de arma y de tu comunidad autónoma, así que prefiero pasarte con un compañero que te lo confirme con exactitud.'],
                    ['question' => '¿Vendéis munición?', 'answer' => 'Sí, trabajamos munición para caza y tiro; para la compra necesitarás acreditar la licencia correspondiente. Te paso con un agente para concretarlo.'],
                ],
                'keywords' => ['licencia de armas', 'licencia de caza', 'permiso de armas', 'munición', 'municion', 'cartuchos', 'guía de pertenencia', 'guia de pertenencia', 'licencia d', 'licencia e', 'federarme', 'federación de caza', 'comprar un arma', 'comprar una escopeta', 'comprar un rifle'],
                'filters' => null,
                'test_questions' => [
                    ['question' => '¿Qué necesito para comprar una escopeta de caza?', 'expect_tools' => [], 'expect_escalate' => true, 'must_contain' => [], 'must_not_contain' => []],
                    ['question' => '¿Puedo comprar munición sin licencia?', 'expect_tools' => [], 'expect_escalate' => true, 'must_contain' => [], 'must_not_contain' => []],
                ],
            ],
            [
                'key' => 'queja_incidencia',
                'channel' => null,
                'name' => 'Queja, incidencia o producto defectuoso',
                'description' => 'El cliente se queja, reporta un producto defectuoso, roto, o un envío equivocado.',
                'priority' => 50,
                'is_active' => true,
                'instructions' => 'Empatiza primero con una frase breve. Pide el número de pedido para poder revisarlo. No prometas soluciones concretas (reembolsos, envíos urgentes): eso lo confirma el agente. Escala siempre, incluso si el cliente no lo pide explícitamente.',
                'allowed_tools' => ['answer_customer', 'escalate_to_agent', 'lookup_order'],
                'knowledge_keys' => ['contacto_tiendas'],
                'escalation' => 'always',
                'escalation_message' => 'Lamento mucho lo ocurrido. Te paso con un agente para resolverlo cuanto antes.',
                'examples' => [
                    ['question' => 'Me ha llegado el pedido roto', 'answer' => 'Cuánto lo siento. Dame el número de pedido y te paso con un compañero para solucionarlo cuanto antes.'],
                    ['question' => 'Me habéis enviado un producto equivocado', 'answer' => 'Perdona las molestias, vamos a arreglarlo. Te paso con un agente con tu pedido a mano.'],
                ],
                'keywords' => ['queja', 'reclamación', 'reclamacion', 'producto defectuoso', 'llegó roto', 'llego roto', 'pedido equivocado', 'mal estado', 'no funciona', 'está roto', 'esta roto', 'incidencia'],
                'filters' => null,
                'test_questions' => [
                    ['question' => 'El producto me ha llegado defectuoso, no funciona', 'expect_tools' => [], 'expect_escalate' => true, 'must_contain' => [], 'must_not_contain' => []],
                    ['question' => 'Habéis enviado mi pedido equivocado, estoy muy enfadado', 'expect_tools' => [], 'expect_escalate' => true, 'must_contain' => [], 'must_not_contain' => []],
                ],
            ],
            [
                'key' => 'general',
                'channel' => null,
                'name' => 'Consulta general',
                'description' => 'Cualquier consulta que no encaje claramente en los demás casos: saludos, preguntas genéricas sobre la tienda o su catálogo.',
                'priority' => -100,
                'is_active' => true,
                'instructions' => 'Responde de forma breve y cercana. Si la pregunta encaja mejor en otro tema (producto, pedido, devolución, envío/pago, armas, queja), guía la conversación hacia ahí. Si no puedes ayudar, ofrece pasar con un agente.',
                'allowed_tools' => null,
                'knowledge_keys' => null,
                'escalation' => 'on_doubt',
                'escalation_message' => 'Te paso con un compañero para ayudarte mejor.',
                'examples' => [
                    ['question' => 'Hola, ¿qué tal?', 'answer' => '¡Hola! Soy el asistente de Álvarez 🙂 ¿En qué puedo ayudarte: buscar un producto, tu pedido o alguna duda de la tienda?'],
                    ['question' => '¿Tenéis tienda física?', 'answer' => 'Sí, tenemos varias tiendas físicas; puedes ver ubicaciones y horarios en https://www.a-alvarez.com/tiendas'],
                ],
                'keywords' => [],
                'filters' => null,
                'test_questions' => [
                    ['question' => 'Hola', 'expect_tools' => [], 'expect_escalate' => false, 'must_contain' => [], 'must_not_contain' => []],
                    ['question' => '¿Tenéis tiendas físicas?', 'expect_tools' => [], 'expect_escalate' => false, 'must_contain' => [], 'must_not_contain' => []],
                ],
            ],
        ];
    }
}
