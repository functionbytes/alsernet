<?php

return [

    // Cart recovery
    'cart_recovery' => [
        'subject' => '¿Olvidaste algo en tu carrito?',
        'heading' => 'Hola :first_name, ¿olvidaste algo?',
        'body' => 'Notamos que dejaste estos productos en tu carrito. ¡Todavía los tienes reservados!',
        'total' => 'Total',
        'cta' => 'Completar mi compra',
    ],

    // Price drop
    'price_drop' => [
        'subject' => 'Bajó el precio del producto que querías',
        'heading' => 'Hola :first_name',
        'body' => 'El producto que tenías en mente ha bajado de precio. ¡Es el momento de comprarlo!',
        'was' => 'Precio anterior',
        'now' => 'Nuevo precio',
        'discount' => '-:percent% de descuento',
        'cta' => 'Ver producto',
    ],

    // Back in stock
    'back_in_stock' => [
        'subject' => 'Vuelve disponible: el producto que esperabas',
        'heading' => 'Hola :first_name',
        'body' => '¡Buenas noticias! El producto que esperabas vuelve a estar disponible. No esperes demasiado, el stock puede agotarse pronto.',
        'available_now' => 'Disponible ahora',
        'cta' => 'Comprar ahora',
    ],

    // Replenishment
    'replenishment' => [
        'subject' => '¿Es momento de reponer?',
        'heading' => 'Hola :first_name',
        'body' => 'Ha pasado un tiempo desde tu última compra de este producto. Según tus hábitos de consumo, podría ser un buen momento para reponerlo.',
        'subtitle' => 'Tu pedido habitual listo en pocos clics. Sin esperas, sin complicaciones.',
        'cta' => 'Volver a pedir',
    ],

    // Win-back
    'win_back' => [
        'subject' => 'Te echamos de menos — descuento exclusivo',
        'heading' => 'Hola :first_name',
        'body' => 'Ha pasado un tiempo desde que no te vemos por aquí. Para celebrar tu vuelta, tenemos un descuento exclusivo esperándote.',
        'coupon_label' => 'Tu descuento exclusivo',
        'coupon_description' => '10% de descuento en tu próximo pedido',
        'limited' => 'El descuento es válido por tiempo limitado.',
        'cta' => 'Volver a la tienda',
    ],

    // Double opt-in
    'double_optin' => [
        'subject' => 'Confirma tu suscripción',
        'greeting' => 'Hola:name,',
        'body' => 'Hemos recibido tu solicitud de suscripción a nuestras comunicaciones de marketing. Para confirmar y empezar a recibir nuestros emails, haz clic en el botón a continuación.',
        'cta' => 'Confirmar suscripción',
        'fallback' => 'Si no puedes hacer clic en el botón, copia y pega este enlace en tu navegador:',
        'ignore' => 'Si no solicitaste esta suscripción, ignora este correo. No realizaremos ninguna acción sin tu confirmación.',
    ],

    // Common footer
    'common' => [
        'unsubscribe' => 'Si no deseas recibir estos recordatorios, puedes :link en cualquier momento.',
        'unsubscribe_link' => 'darte de baja',
    ],

];
