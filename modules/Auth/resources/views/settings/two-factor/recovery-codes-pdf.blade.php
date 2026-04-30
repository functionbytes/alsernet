<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Códigos de recuperación 2FA</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #2a2a2a; padding: 30px; }
        .header { border-bottom: 3px solid #b10100; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 22px; color: #b10100; }
        .meta { font-size: 11px; color: #666; margin-top: 4px; }
        .warning { background: #fff5e1; border-left: 4px solid #FEC90F; padding: 12px 16px; margin: 20px 0; font-size: 12px; }
        .codes { margin: 24px 0; }
        .code { display: inline-block; width: 45%; padding: 10px 14px; margin: 0 2% 8px 0; border: 1px solid #ddd; font-family: monospace; font-size: 14px; text-align: center; background: #f9f9f9; }
        .footer { margin-top: 30px; padding-top: 14px; border-top: 1px solid #eee; font-size: 10px; color: #888; text-align: center; }
        h3 { font-size: 14px; margin: 20px 0 8px 0; }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $appName }} — Códigos de recuperación 2FA</h1>
    <div class="meta">
        Usuario: <strong>{{ $user->firstname }} {{ $user->lastname }}</strong> ({{ $user->email }})<br>
        Generado: {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</div>

<div class="warning">
    <strong>Importante:</strong> Guarda estos códigos en un lugar seguro. Cada código puede usarse
    <strong>solo una vez</strong> si pierdes acceso a tu app autenticadora. Al agotarlos, tendrás que
    regenerar códigos nuevos desde Configuración &rarr; 2FA.
</div>

<h3>Tus códigos de recuperación</h3>
<div class="codes">
    @foreach ($codes as $code)
        <div class="code">{{ $code }}</div>
    @endforeach
</div>

<div class="footer">
    Este documento es confidencial. Nunca lo compartas ni lo publiques.
    Si sospechas que alguien más lo vio, regenera los códigos inmediatamente.
</div>

</body>
</html>
