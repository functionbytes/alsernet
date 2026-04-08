import React, { useEffect, useRef } from 'react';

interface Message {
  id: number;
  conversation_id: number;
  author_id?: number;
  user_id?: number;
  type: string;
  body: string;
  html_body?: string;
  is_internal: boolean;
  created_at: string;
  sender_name: string;
  sender_avatar?: string;
  attachment_urls?: string[];
  metadata?: Record<string, any>;
}

interface MessageListProps {
  messages: Message[];
  currentUserId: number;
  isLoading?: boolean;
  onMessageRead?: (messageId: number) => void;
}

/**
 * MessageList - Componente para mostrar mensajes en tiempo real
 * Características:
 * - Auto-scroll al nuevo mensaje
 * - Agrupación por fecha
 * - Avatares y timestamps
 * - Soporte para eventos del sistema
 * - Lazy loading de imágenes
 */
export function MessageList({
  messages,
  currentUserId,
  isLoading = false,
  onMessageRead,
}: MessageListProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const endRef = useRef<HTMLDivElement>(null);

  // Auto-scroll al final cuando hay nuevos mensajes
  useEffect(() => {
    endRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages.length]);

  // Marcar mensajes como leído cuando son visibles
  useEffect(() => {
    if (!onMessageRead || !containerRef.current) return;

    const observer = new IntersectionObserver(
      entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const messageId = parseInt(entry.target.getAttribute('data-message-id') || '0');
            if (messageId) {
              onMessageRead(messageId);
            }
          }
        });
      },
      { threshold: 0.5 }
    );

    // Observar todos los mensajes
    containerRef.current.querySelectorAll('[data-message-id]').forEach(el => {
      observer.observe(el);
    });

    return () => observer.disconnect();
  }, [messages, onMessageRead]);

  // Agrupar mensajes por fecha
  const messagesByDate = messages.reduce((acc, message) => {
    const date = new Date(message.created_at).toLocaleDateString('es-ES');
    if (!acc[date]) acc[date] = [];
    acc[date].push(message);
    return acc;
  }, {} as Record<string, Message[]>);

  const renderMessage = (message: Message) => {
    if (message.type === 'message') {
      return (
        <div
          key={message.id}
          data-message-id={message.id}
          className={`mb-3 d-flex gap-3 ${
            message.user_id ? 'justify-content-end' : 'justify-content-start'
          }`}
        >
          {!message.user_id && message.sender_avatar && (
            <img
              src={message.sender_avatar}
              alt={message.sender_name}
              className="rounded-circle"
              width="40"
              height="40"
              loading="lazy"
            />
          )}

          <div
            className={`p-3 rounded ${
              message.user_id
                ? 'bg-primary-subtle text-dark'
                : message.is_internal
                ? 'bg-warning-subtle text-dark'
                : 'bg-light text-dark'
            }`}
            style={{ maxWidth: '70%', wordWrap: 'break-word' }}
          >
            {/* Nombre del remitente */}
            <small className="d-block fw-semibold mb-1">
              {message.sender_name}
              {message.is_internal && <span className="ms-2 badge bg-warning">Interna</span>}
            </small>

            {/* Contenido del mensaje */}
            <div className="mb-2">
              {message.html_body ? (
                <div dangerouslySetInnerHTML={{ __html: message.html_body }} />
              ) : (
                <p className="mb-0">{message.body}</p>
              )}
            </div>

            {/* Adjuntos */}
            {message.attachment_urls && message.attachment_urls.length > 0 && (
              <div className="mt-2 pt-2 border-top">
                <small className="d-block mb-2">
                  <i className="fa fa-paperclip"></i> {message.attachment_urls.length} adjunto(s)
                </small>
                {message.attachment_urls.map((url, idx) => (
                  <a
                    key={idx}
                    href={url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="btn btn-sm btn-light d-block mb-1"
                  >
                    <i className="fa fa-download"></i> Descargar
                  </a>
                ))}
              </div>
            )}

            {/* Timestamp */}
            <small className="text-muted d-block mt-2">
              {new Date(message.created_at).toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
              })}
            </small>
          </div>

          {message.user_id && message.sender_avatar && (
            <img
              src={message.sender_avatar}
              alt={message.sender_name}
              className="rounded-circle"
              width="40"
              height="40"
              loading="lazy"
            />
          )}
        </div>
      );
    }

    // Eventos del sistema
    return (
      <div key={message.id} className="text-center mb-3">
        <div className="bg-light-subtle p-2 rounded d-inline-block">
          <small className="text-muted">
            <i className="fa fa-circle-info"></i> {message.body}
          </small>
        </div>
      </div>
    );
  };

  if (isLoading) {
    return (
      <div className="text-center py-4">
        <div className="spinner-border text-primary" role="status">
          <span className="visually-hidden">Cargando...</span>
        </div>
      </div>
    );
  }

  if (messages.length === 0) {
    return (
      <div className="text-center py-4">
        <i className="fa fa-inbox" style={{ fontSize: '48px', opacity: 0.3 }}></i>
        <p className="text-muted mt-2">No hay mensajes en esta conversación</p>
      </div>
    );
  }

  return (
    <div
      ref={containerRef}
      className="messages-list"
      style={{ height: '400px', overflowY: 'auto', paddingRight: '10px' }}
    >
      {Object.entries(messagesByDate).map(([date, dateMessages]) => (
        <div key={date}>
          {/* Separador de fecha */}
          <div className="text-center mb-3 mt-3">
            <small className="text-muted bg-light px-3 py-1 rounded">
              {new Date(date).toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
              })}
            </small>
          </div>

          {/* Mensajes del día */}
          {dateMessages.map(message => renderMessage(message))}
        </div>
      ))}

      {/* Punto de referencia para auto-scroll */}
      <div ref={endRef} />
    </div>
  );
}
