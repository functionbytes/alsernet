<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¿Es momento de reponer?</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333333;">

    <div style="text-align: center; margin-bottom: 24px;">
        <div style="background: #90bb13; padding: 16px; border-radius: 6px 6px 0 0;">
            <span style="color: white; font-size: 20px; font-weight: bold;">¿Necesitas reponer?</span>
        </div>
    </div>

    <h2 style="margin: 0 0 12px;">Hola {{ $customer->first_name ?? '' }}</h2>
    <p style="color: #555555; margin-bottom: 24px;">
        Ha pasado un tiempo desde tu última compra de este producto. Según tus hábitos de consumo, podría ser un buen momento para reponerlo.
    </p>

    <div style="border: 1px solid #e0e0e0; border-radius: 6px; padding: 20px; margin-bottom: 24px; text-align: center;">
        <div style="font-size: 40px; margin-bottom: 12px;">🔄</div>
        <p style="margin: 0; color: #555555;">
            Tu pedido habitual listo en pocos clics.<br>
            Sin esperas, sin complicaciones.
        </p>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="#" style="display: inline-block; background: #90bb13; color: white; padding: 14px 28px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
            Volver a pedir
        </a>
    </div>

    <p style="font-size: 12px; color: #999999; text-align: center; margin-top: 32px;">
        Si no deseas recibir estos recordatorios, puedes
        <a href="#" style="color: #999999;">darte de baja</a> en cualquier momento.
    </p>

</body>
</html>
