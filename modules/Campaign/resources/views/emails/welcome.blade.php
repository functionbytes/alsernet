<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Bienvenido</title></head>
<body style="margin:0;padding:40px 20px;background:#f4f4f7;font-family:-apple-system,Segoe UI,Helvetica,Arial,sans-serif;">
    <table align="center" width="540" cellpadding="0" cellspacing="0" style="background:white;border-radius:8px;overflow:hidden;">
        <tr><td style="padding:40px;">
            <h1 style="margin:0 0 16px;color:#22c55e;">¡Bienvenido!</h1>
            <p style="margin:0 0 16px;color:#444;line-height:1.6;font-size:16px;">
                Hola {{ $subscriber->first_name ?? '' }} 👋
            </p>
            <p style="margin:0 0 16px;color:#444;line-height:1.6;font-size:16px;">
                Tu suscripción a <strong>{{ $list->name }}</strong> está confirmada. Te enviaremos contenido útil y, prometido, sin spam.
            </p>
            <p style="margin:24px 0 0;color:#888;font-size:13px;">
                Si quieres cambiar tus preferencias en cualquier momento, <a href="{{ $manageUrl }}" style="color:#0d6efd;">haz click aquí</a>.
            </p>
        </td></tr>
    </table>
</body>
</html>
