import React, { useState, useCallback } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';

interface MessageComposerProps {
  conversationId: number;
  onSubmit: (body: string, htmlBody: string, attachments: File[]) => Promise<void>;
  cannedReplies?: Array<{ id: number; title: string; body: string; html_body: string }>;
  isSubmitting?: boolean;
  isInternal?: boolean;
  onInternalChange?: (isInternal: boolean) => void;
}

/**
 * MessageComposer - Editor de mensajes con TipTap
 * Características:
 * - Rich text editing con toolbar
 * - Soporte para imágenes
 * - Adjuntos de archivos
 * - Plantillas predefinidas (canned replies)
 * - Indicador de escritura
 * - Opción de nota interna
 */
export function MessageComposer({
  conversationId,
  onSubmit,
  cannedReplies = [],
  isSubmitting = false,
  isInternal = false,
  onInternalChange,
}: MessageComposerProps) {
  const [attachments, setAttachments] = useState<File[]>([]);
  const [isTyping, setIsTyping] = useState(false);

  // Configurar editor TipTap
  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        bulletList: { keepMarks: true, keepAttributes: false },
        orderedList: { keepMarks: true, keepAttributes: false },
      }),
      Image.configure({ allowBase64: true }),
      Link.configure({ openOnClick: false }),
    ],
    content: '',
  });

  // Manejar envío del mensaje
  const handleSubmit = useCallback(async () => {
    if (!editor || !editor.getText().trim()) {
      alert('El mensaje no puede estar vacío');
      return;
    }

    try {
      const htmlBody = editor.getHTML();
      await onSubmit(editor.getText(), htmlBody, attachments);

      // Limpiar editor
      editor.commands.clearContent();
      setAttachments([]);
      setIsTyping(false);
    } catch (error) {
      console.error('Error sending message:', error);
    }
  }, [editor, attachments, onSubmit]);

  // Manejar cambio de archivos
  const handleFileChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      if (e.target.files) {
        setAttachments(prev => [...prev, ...Array.from(e.target.files!)]);
      }
    },
    []
  );

  // Eliminar adjunto
  const removeAttachment = useCallback((index: number) => {
    setAttachments(prev => prev.filter((_, i) => i !== index));
  }, []);

  // Insertar plantilla
  const insertCannedReply = useCallback(
    (reply: (typeof cannedReplies)[0]) => {
      if (editor) {
        editor.commands.setContent(reply.html_body || reply.body);
      }
    },
    [editor]
  );

  // Indicador de escritura
  const handleEditorChange = useCallback(() => {
    if (!isTyping) {
      setIsTyping(true);
      // Broadcast typing event
      fetch(`/api/helpdesk/conversations/${conversationId}/typing`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ is_typing: true }),
      }).catch(err => console.error('Error:', err));
    }
  }, [conversationId, isTyping]);

  if (!editor) return null;

  return (
    <div className="message-composer card">
      <div className="card-header d-flex justify-content-between align-items-center">
        <h6 className="mb-0">Responder Mensaje</h6>

        {/* Checkbox de nota interna */}
        <div className="form-check">
          <input
            type="checkbox"
            className="form-check-input"
            id="internalNote"
            checked={isInternal}
            onChange={e => onInternalChange?.(e.target.checked)}
            disabled={isSubmitting}
          />
          <label className="form-check-label" htmlFor="internalNote">
            <small>Nota interna (solo agentes)</small>
          </label>
        </div>
      </div>

      <div className="card-body">
        {{/* Toolbar */}}
        <div className="editor-toolbar mb-2 btn-group btn-group-sm" role="group">
          <button
            type="button"
            className="btn btn-light"
            onClick={() => editor.chain().focus().toggleBold().run()}
            disabled={!editor.can().chain().focus().toggleBold().run() || isSubmitting}
            title="Negrita (Ctrl+B)"
          >
            <i className="fa fa-bold"></i>
          </button>

          <button
            type="button"
            className="btn btn-light"
            onClick={() => editor.chain().focus().toggleItalic().run()}
            disabled={!editor.can().chain().focus().toggleItalic().run() || isSubmitting}
            title="Itálica (Ctrl+I)"
          >
            <i className="fa fa-italic"></i>
          </button>

          <button
            type="button"
            className="btn btn-light"
            onClick={() => editor.chain().focus().toggleStrike().run()}
            disabled={!editor.can().chain().focus().toggleStrike().run() || isSubmitting}
            title="Tachado"
          >
            <i className="fa fa-strikethrough"></i>
          </button>

          <div className="vr"></div>

          <button
            type="button"
            className="btn btn-light"
            onClick={() => editor.chain().focus().toggleBulletList().run()}
            disabled={isSubmitting}
            title="Lista"
          >
            <i className="fa fa-list"></i>
          </button>

          <button
            type="button"
            className="btn btn-light"
            onClick={() => editor.chain().focus().toggleOrderedList().run()}
            disabled={isSubmitting}
            title="Lista numerada"
          >
            <i className="fa fa-list-ol"></i>
          </button>

          <button
            type="button"
            className="btn btn-light"
            onClick={() => editor.chain().focus().toggleCodeBlock().run()}
            disabled={isSubmitting}
            title="Código"
          >
            <i className="fa fa-code"></i>
          </button>

          <div className="vr"></div>

          <button
            type="button"
            className="btn btn-light"
            onClick={() => {
              const url = prompt('URL de la imagen:');
              if (url) editor.chain().focus().setImage({ src: url }).run();
            }}
            disabled={isSubmitting}
            title="Insertar imagen"
          >
            <i className="fa fa-image"></i>
          </button>

          <button
            type="button"
            className="btn btn-light"
            onClick={() => {
              const url = prompt('URL del enlace:');
              if (url) editor.chain().focus().setLink({ href: url }).run();
            }}
            disabled={isSubmitting}
            title="Insertar enlace"
          >
            <i className="fa fa-link"></i>
          </button>
        </div>

        {/* Editor */}
        <div className="editor-wrapper border rounded p-3 mb-3" style={{ minHeight: '150px' }}>
          <EditorContent editor={editor} onChange={handleEditorChange} />
        </div>

        {/* Adjuntos */}
        <div className="mb-3">
          <label className="form-label">Adjuntos</label>
          <input
            type="file"
            multiple
            className="form-control"
            onChange={handleFileChange}
            disabled={isSubmitting}
            accept="*/*"
          />
          <small className="text-muted">Máximo 10 MB por archivo</small>

          {attachments.length > 0 && (
            <div className="mt-2">
              <div className="list-group list-group-sm">
                {attachments.map((file, idx) => (
                  <div key={idx} className="list-group-item d-flex justify-content-between align-items-center">
                    <small>
                      <i className="fa fa-file"></i> {file.name}
                    </small>
                    <button
                      type="button"
                      className="btn btn-sm btn-light-danger"
                      onClick={() => removeAttachment(idx)}
                      disabled={isSubmitting}
                    >
                      <i className="fa fa-xmark"></i>
                    </button>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Plantillas */}
        {cannedReplies.length > 0 && (
          <div className="mb-3">
            <div className="dropdown">
              <button
                className="btn btn-sm btn-light dropdown-toggle"
                type="button"
                data-bs-toggle="dropdown"
                disabled={isSubmitting}
              >
                <i className="fa fa-file"></i> Plantillas
              </button>
              <ul className="dropdown-menu">
                {cannedReplies.map(reply => (
                  <li key={reply.id}>
                    <a
                      className="dropdown-item"
                      href="#"
                      onClick={e => {
                        e.preventDefault();
                        insertCannedReply(reply);
                      }}
                    >
                      {reply.title}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        )}
      </div>

      {/* Botones de acción */}
      <div className="card-footer d-flex justify-content-between">
        <div className="text-muted">
          {editor.storage.characterCount?.characters() || 0} caracteres
        </div>

        <div className="d-flex gap-2">
          <button
            type="button"
            className="btn btn-light"
            onClick={() => editor.commands.clearContent()}
            disabled={isSubmitting}
          >
            Limpiar
          </button>

          <button
            type="button"
            className="btn btn-primary"
            onClick={handleSubmit}
            disabled={isSubmitting || !editor.getText().trim()}
          >
            {isSubmitting ? (
              <>
                <span className="spinner-border spinner-border-sm me-2"></span>
                Enviando...
              </>
            ) : (
              <>
                <i className="fa fa-paper-plane"></i> Enviar
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
}
