<?php

return [

    // Cart recovery
    'cart_recovery' => [
        'subject' => 'Esqueceu algo no seu carrinho?',
        'heading' => 'Olá :first_name, esqueceu algo?',
        'body' => 'Notamos que você deixou estes produtos no seu carrinho. Eles ainda estão reservados para você!',
        'total' => 'Total',
        'cta' => 'Concluir minha compra',
    ],

    // Price drop
    'price_drop' => [
        'subject' => 'O preço caiu no produto que você queria',
        'heading' => 'Olá :first_name',
        'body' => 'O produto que você tinha em mente teve uma queda de preço. Este é o momento perfeito para comprá-lo!',
        'was' => 'Antes',
        'now' => 'Agora',
        'discount' => '-:percent% de desconto',
        'cta' => 'Ver produto',
    ],

    // Back in stock
    'back_in_stock' => [
        'subject' => 'De volta ao estoque: o produto que você estava esperando',
        'heading' => 'Olá :first_name',
        'body' => 'Ótimas notícias! O produto que você estava esperando voltou ao estoque. Não espere muito, o estoque pode acabar em breve.',
        'available_now' => 'Disponível agora',
        'cta' => 'Comprar agora',
    ],

    // Replenishment
    'replenishment' => [
        'subject' => 'Hora de reabastecer?',
        'heading' => 'Olá :first_name',
        'body' => 'Já faz um tempo desde a sua última compra deste produto. Com base nos seus hábitos de compra, pode ser um bom momento para reabastecer.',
        'subtitle' => 'Seu pedido habitual pronto em poucos cliques. Sem espera, sem complicações.',
        'cta' => 'Pedir novamente',
    ],

    // Win-back
    'win_back' => [
        'subject' => 'Sentimos sua falta — desconto exclusivo',
        'heading' => 'Olá :first_name',
        'body' => 'Já faz um tempo que não te vemos por aqui. Para celebrar seu retorno, temos um desconto exclusivo esperando por você.',
        'coupon_label' => 'Seu desconto exclusivo',
        'coupon_description' => '10% de desconto no seu próximo pedido',
        'limited' => 'Este desconto é válido por tempo limitado.',
        'cta' => 'Voltar à loja',
    ],

    // Double opt-in
    'double_optin' => [
        'subject' => 'Confirme sua assinatura',
        'greeting' => 'Olá:name,',
        'body' => 'Recebemos sua solicitação de assinatura das nossas comunicações de marketing. Para confirmar e começar a receber nossos e-mails, clique no botão abaixo.',
        'cta' => 'Confirmar assinatura',
        'fallback' => 'Se você não puder clicar no botão, copie e cole este link no seu navegador:',
        'ignore' => 'Se você não solicitou esta assinatura, ignore este e-mail. Não tomaremos nenhuma ação sem a sua confirmação.',
    ],

    // Common footer
    'common' => [
        'unsubscribe' => 'Se não deseja receber esses lembretes, você pode :link a qualquer momento.',
        'unsubscribe_link' => 'cancelar a inscrição',
    ],

];
