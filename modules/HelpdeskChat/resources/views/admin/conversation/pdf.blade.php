<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversación #{{ $conversation->id }} - Transcripción</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .conversation-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-open { background-color: #3498db; color: white; }
        .status-pending { background-color: #f39c12; color: white; }
        .status-resolved { background-color: #27ae60; color: white; }

        .priority-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .priority-low { background-color: #95a5a6; color: white; }
        .priority-medium { background-color: #3498db; color: white; }
        .priority-high { background-color: #e67e22; color: white; }
        .priority-urgent { background-color: #e74c3c; color: white; }

        .messages-section {
            margin-top: 30px;
        }
        .messages-section h2 {
            color: #2c3e50;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
        }
        .message {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 5px;
            page-break-inside: avoid;
        }
        .message-incoming {
            background-color: #f0f0f0;
            border-left: 4px solid #3498db;
        }
        .message-outgoing {
            background-color: #e3f2fd;
            border-left: 4px solid #27ae60;
        }
        .message-activity {
            background-color: #fff9e6;
            border-left: 4px solid #f39c12;
            font-style: italic;
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 11px;
        }
        .message-sender {
            font-weight: bold;
            color: #2c3e50;
        }
        .message-time {
            color: #7f8c8d;
        }
        .message-content {
            color: #333;
            word-wrap: break-word;
        }
        .message-attachments {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #ddd;
            font-size: 10px;
            color: #555;
        }
        .attachment-item {
            margin-top: 4px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #7f8c8d;
            text-align: center;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Transcripción de Conversación</h1>
        <p>Conversación #{{ $conversation->id }}</p>
    </div>

    <div class="conversation-info">
        <div class="info-row">
            <span class="info-label">Cliente:</span>
            <span class="info-value">{{ $conversation->contact->name }}</span>
        </div>
        @if($conversation->contact->email)
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">{{ $conversation->contact->email }}</span>
        </div>
        @endif
        @if($conversation->contact->phone_number)
        <div class="info-row">
            <span class="info-label">Teléfono:</span>
            <span class="info-value">{{ $conversation->contact->phone_number }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Canal:</span>
            <span class="info-value">{{ $conversation->inbox->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span class="info-value">
                <span class="status-badge status-{{ $conversation->status }}">
                    {{ __(ucfirst($conversation->status)) }}
                </span>
            </span>
        </div>
        @if($conversation->priority)
        <div class="info-row">
            <span class="info-label">Prioridad:</span>
            <span class="info-value">
                <span class="priority-badge priority-{{ $conversation->priority }}">
                    {{ __(ucfirst($conversation->priority)) }}
                </span>
            </span>
        </div>
        @endif
        @if($conversation->assignee)
        <div class="info-row">
            <span class="info-label">Asignado a:</span>
            <span class="info-value">{{ $conversation->assignee->name }}</span>
        </div>
        @endif
        @if($conversation->team)
        <div class="info-row">
            <span class="info-label">Equipo:</span>
            <span class="info-value">{{ $conversation->team->name }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Creada:</span>
            <span class="info-value">{{ $conversation->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Última actividad:</span>
            <span class="info-value">{{ $conversation->last_activity_at->format('d/m/Y H:i') }}</span>
        </div>
        @if($conversation->resolved_at)
        <div class="info-row">
            <span class="info-label">Resuelta:</span>
            <span class="info-value">{{ $conversation->resolved_at->format('d/m/Y H:i') }}</span>
        </div>
        @endif
        @if($conversation->cached_label_list)
        <div class="info-row">
            <span class="info-label">Etiquetas:</span>
            <span class="info-value">{{ str_replace(',', ', ', $conversation->cached_label_list) }}</span>
        </div>
        @endif
    </div>

    <div class="messages-section">
        <h2>Mensajes ({{ $conversation->messages->count() }})</h2>

        @foreach($conversation->messages as $message)
            <div class="message message-{{ $message->message_type->value }}">
                <div class="message-header">
                    <span class="message-sender">
                        @if($message->isFromUser())
                            👤 {{ $message->sender->name ?? 'Agente' }}
                        @elseif($message->isFromContact())
                            💬 {{ $message->sender->name ?? 'Cliente' }}
                        @else
                            🤖 Sistema
                        @endif
                    </span>
                    <span class="message-time">
                        {{ $message->created_at->format('d/m/Y H:i:s') }}
                    </span>
                </div>
                <div class="message-content">
                    {!! nl2br(e($message->content)) !!}
                </div>

                @if($message->hasAttachments())
                    <div class="message-attachments">
                        <strong>📎 Archivos adjuntos:</strong>
                        @foreach($message->getAttachments() as $attachment)
                            <div class="attachment-item">
                                • {{ $attachment->file_name }} ({{ number_format($attachment->size / 1024, 2) }} KB)
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="footer">
        <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>{{ config('app.name') }} - Sistema de Gestión de Conversaciones</p>
    </div>
</body>
</html>
