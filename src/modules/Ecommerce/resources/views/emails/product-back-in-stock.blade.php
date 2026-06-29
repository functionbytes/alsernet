<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Producto disponible</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; color: #333; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; }
        .header { background: #90bb13; padding: 24px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; }
        .content { padding: 24px; }
        .content p { margin-bottom: 12px; line-height: 1.6; }
        .product-box { border: 1px solid #e0e0e0; border-radius: 6px; padding: 16px; margin: 20px 0; }
        .product-box h2 { font-size: 18px; margin-bottom: 8px; color: #333; }
        .product-box .price { font-size: 20px; color: #90bb13; font-weight: bold; }
        .btn { display: inline-block; background: #90bb13; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 4px; font-weight: bold; font-size: 15px; margin: 16px 0; }
        .footer { background: #f5f5f5; padding: 16px; text-align: center; font-size: 12px; color: #888; }
        .footer p { margin: 2px 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Producto disponible</h1>
        </div>

        <div class="content">
            <p>Hola,</p>
            <p>Te escribimos porque solicitaste que te avisáramos cuando el siguiente producto volviera a estar disponible.</p>

            <div class="product-box">
                @if($product->featured_image)
                    <img src="{{ $product->featured_image }}" alt="{{ $product->name }}" width="120" style="border-radius: 4px; margin-bottom: 12px; display: block;">
                @endif
                <h2>{{ $product->name }}</h2>
                @if($product->description)
                    <p style="color: #666; font-size: 14px; margin-top: 6px;">{{ Str::limit($product->description, 120) }}</p>
                @endif
                <div class="price">${{ number_format($product->final_price, 2) }}</div>
            </div>

            <p>El producto ya está disponible. No esperes demasiado, el stock puede agotarse nuevamente.</p>

            <a href="{{ route('shop.product', $product->slug) }}" class="btn">Ver producto</a>

            <p>Si tienes alguna pregunta, puedes contactarnos respondiendo a este correo.</p>
        </div>

        <div class="footer">
            <p>{{ config('app.name') }}</p>
            <p>Recibiste este correo porque solicitaste una alerta de restock. Si no quieres más estos avisos, simplemente ignora este mensaje.</p>
        </div>
    </div>
</body>
</html>
