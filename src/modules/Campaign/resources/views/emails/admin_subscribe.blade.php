<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Notificación de lista</title></head>
<body style="font-family:-apple-system,Segoe UI,Helvetica,sans-serif;padding:24px;color:#333;">
    <h3>{{ $event === 'subscribed' ? '✅ Nueva suscripción' : '❌ Desuscripción' }}</h3>
    <p>En la lista <strong>{{ $list->name }}</strong>:</p>
    <table cellpadding="6" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:4px;background:#f9fafb;">
        <tr><td><strong>Email:</strong></td><td>{{ $subscriber->email }}</td></tr>
        <tr><td><strong>Nombre:</strong></td><td>{{ trim($subscriber->first_name.' '.$subscriber->last_name) ?: '—' }}</td></tr>
        <tr><td><strong>IP:</strong></td><td>{{ $subscriber->ip ?? '—' }}</td></tr>
        <tr><td><strong>Fecha:</strong></td><td>{{ now()->format('Y-m-d H:i:s') }}</td></tr>
    </table>
</body></html>
