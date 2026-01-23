<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversación #{{ $conversation->id }} - Imprimir</title>
    <style>
        @media print {
            @page {
                margin: 2cm;
                size: A4;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-after: always;
            }

            .avoid-break {
                page-break-inside: avoid;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
            background: white;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            border-bottom: 3px solid #3498db;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 28pt;
            margin-bottom: 10px;
        }

        .header .subtitle {
            color: #7f8c8d;
            font-size: 12pt;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px 30px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-value {
            color: #2c3e50;
            font-size: 11pt;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-open { background-color: #3498db; color: white; }
        .badge-pending { background-color: #f39c12; color: white; }
        .badge-resolved { background-color: #27ae60; color: white; }
        .badge-low { background-color: #95a5a6; color: white; }
        .badge-medium { background-color: #3498db; color: white; }
        .badge-high { background-color: #e67e22; color: white; }
        .badge-urgent { background-color: #e74c3c; color: white; }

        .messages-section {
            margin-top: 40px;
        }

        .section-title {
            color: #2c3e50;
            font-size: 18pt;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        .message {
            margin-bottom: 25px;
            padding: 15px;
            border-radius: 8px;
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
            font-size: 10pt;
        }

        .message-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .message-sender {
            font-weight: 600;
            color: #2c3e50;
            font-size: 11pt;
        }

        .message-time {
            color: #7f8c8d;
            font-size: 9pt;
        }

        .message-content {
            color: #2c3e50;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .attachments {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #ddd;
        }

        .attachments-title {
            font-weight: 600;
            font-size: 9pt;
            color: #555;
            margin-bottom: 8px;
        }

        .attachment {
            padding: 6px 0;
            font-size: 10pt;
            color: #555;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
            text-align: center;
            color: #7f8c8d;
            font-size: 9pt;
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            z-index: 1000;
        }

        .print-button:hover {
            background-color: #2980b9;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 12px 24px;
            background-color: #95a5a6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            z-index: 1000;
        }

        .back-button:hover {
            background-color: #7f8c8d;
        }
    </style>
</head>
<body>
    <a href="{{ route('admin.conversation.show', $conversation) }}" class="back-button no-print">← Volver</a>
    <button onclick="window.print()" class="print-button no-print">🖨️ Imprimir</button>

    <div class="container">
        <div class="header">
            <h1>Conversación #{{ $conversation->id }}</h1>
            <p class="subtitle">Transcripción completa para impresión</p>
        </div>

        <div class="info-grid avoid-break">
            <div class="info-item">
                <span class="info-label">Cliente</span>
                <span class="info-value">{{ $conversation->contact->name }}</span>
            </div>

            @if($conversation->contact->email)
            <div class="info-item">
                <span class="info-label">Email</span>
                <span class="info-value">{{ $conversation->contact->email }}</span>
            </div>
            @endif

            @if($conversation->contact->phone_number)
            <div class="info-item">
                <span class="info-label">Teléfono</span>
                <span class="info-value">{{ $conversation->contact->phone_number }}</span>
            </div>
            @endif

            <div class="info-item">
                <span class="info-label">Canal</span>
                <span class="info-value">{{ $conversation->inbox->name }}</span>
            </div>

            <div class="info-item">
                <span class="info-label">Estado</span>
                <span class="info-value">
                    <span class="badge badge-{{ $conversation->status }}">
                        {{ __(ucfirst($conversation->status)) }}
                    </span>
                </span>
            </div>

            @if($conversation->priority)
            <div class="info-item">
                <span class="info-label">Prioridad</span>
                <span class="info-value">
                    <span class="badge badge-{{ $conversation->priority }}">
                        {{ __(ucfirst($conversation->priority)) }}
                    </span>
                </span>
            </div>
            @endif

            @if($conversation->assignee)
            <div class="info-item">
                <span class="info-label">Asignado a</span>
                <span class="info-value">{{ $conversation->assignee->name }}</span>
            </div>
            @endif

            @if($conversation->team)
            <div class="info-item">
                <span class="info-label">Equipo</span>
                <span class="info-value">{{ $conversation->team->name }}</span>
            </div>
            @endif

            <div class="info-item">
                <span class="info-label">Fecha creación</span>
                <span class="info-value">{{ $conversation->created_at->format('d/m/Y H:i') }}</span>
            </div>

            <div class="info-item">
                <span class="info-label">Última actividad</span>
                <span class="info-value">{{ $conversation->last_activity_at->format('d/m/Y H:i') }}</span>
            </div>

            @if($conversation->resolved_at)
            <div class="info-item">
                <span class="info-label">Fecha resolución</span>
                <span class="info-value">{{ $conversation->resolved_at->format('d/m/Y H:i') }}</span>
            </div>
            @endif

            @if($conversation->cached_label_list)
            <div class="info-item">
                <span class="info-label">Etiquetas</span>
                <span class="info-value">{{ str_replace(',', ', ', $conversation->cached_label_list) }}</span>
            </div>
            @endif
        </div>

        <div class="messages-section">
            <h2 class="section-title">Mensajes ({{ $conversation->messages->count() }})</h2>

            @foreach($conversation->messages as $message)
                <div class="message message-{{ $message->message_type->value }} avoid-break">
                    <div class="message-meta">
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

                    <div class="message-content">{{ $message->content }}</div>

                    @if($message->hasAttachments())
                        <div class="attachments">
                            <div class="attachments-title">📎 Archivos adjuntos:</div>
                            @foreach($message->getAttachments() as $attachment)
                                <div class="attachment">
                                    • {{ $attachment->file_name }} ({{ number_format($attachment->size / 1024, 2) }} KB)
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="footer avoid-break">
            <p><strong>{{ config('app.name') }}</strong> - Sistema de Gestión de Conversaciones</p>
            <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
